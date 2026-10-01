<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\ActivityLogger;
use App\Services\PhilippinePhoneNumberService;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RegistrationVerificationController extends Controller
{
    public function __construct(
        private readonly VerificationChallengeService $challengeService,
        private readonly VerificationCodeDeliveryService $deliveryService,
        private readonly PhilippinePhoneNumberService $phoneNumberService,
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Display the customer registration OTP verification page.
     */
    public function create(
        Request $request,
        VerificationChallenge $challenge
    ): View|RedirectResponse {
        $user = $this->registrationUser(
            $challenge
        );

        $this->assertRegistrationSession(
            $request,
            $challenge
        );

        if (
            $challenge->isVerified()
            || $challenge->isConsumed()
            || $user->account_status !== 'pending_verification'
        ) {
            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This registration verification request is no longer available.'
                );
        }

        return view('auth.verify-registration', [
            'challenge' => $challenge,
            'maskedDestination' =>
            $this->maskedDestination($challenge),
            'channelLabel' =>
            $this->channelLabel($challenge),
            'isExpired' =>
            $challenge->isExpired(),
            'resendAvailableIn' =>
            $this->challengeService
                ->resendAvailableIn($challenge),
            'remainingAttempts' =>
            max(
                0,
                VerificationChallengeService::MAX_ATTEMPTS
                    - $challenge->attempts
            ),
        ]);
    }

    /**
     * Verify the registration OTP and activate the customer account.
     */
    public function store(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $this->registrationUser(
            $challenge
        );

        $this->assertRegistrationSession(
            $request,
            $challenge
        );

        $validated = $request->validate(
            [
                'code' => [
                    'bail',
                    'required',
                    'string',
                    'regex:/^\d{6}$/',
                ],
            ],
            [
                'code.required' =>
                'Please enter the six-digit verification code.',
                'code.regex' =>
                'The verification code must contain exactly six digits.',
            ]
        );

        $registrationContext = $request->session()->get(
            'registration.context',
            []
        );

        try {
            $completedChallenge =
                $this->challengeService->complete(
                    challenge: $challenge,
                    code: $validated['code'],
                    expectedPurpose: VerificationChallenge::PURPOSE_REGISTRATION,
                    completion: function (
                        User $lockedUser,
                        VerificationChallenge $lockedChallenge
                    ): void {
                        if (
                            $lockedUser->role !== 'customer'
                            || $lockedUser->account_status
                            !== 'pending_verification'
                            || $lockedUser->activation_completed_at !== null
                        ) {
                            throw new DomainException(
                                'This account cannot be activated through customer registration verification.'
                            );
                        }

                        if (
                            $lockedChallenge->channel
                            === VerificationChallenge::CHANNEL_EMAIL
                        ) {
                            if (
                                mb_strtolower(
                                    trim($lockedUser->email)
                                )
                                !== mb_strtolower(
                                    trim(
                                        $lockedChallenge->destination
                                    )
                                )
                            ) {
                                throw new DomainException(
                                    'The verification email no longer matches this account.'
                                );
                            }

                            $lockedUser->email_verified_at = now();
                        } elseif (
                            $lockedChallenge->channel
                            === VerificationChallenge::CHANNEL_SMS
                        ) {
                            if (
                                trim((string) $lockedUser->phone)
                                !== trim(
                                    $lockedChallenge->destination
                                )
                            ) {
                                throw new DomainException(
                                    'The verification mobile number no longer matches this account.'
                                );
                            }

                            $lockedUser->phone_verified_at = now();
                        } else {
                            throw new DomainException(
                                'The verification channel is invalid.'
                            );
                        }

                        $lockedUser->account_status = 'active';
                        $lockedUser->activation_completed_at = now();
                        $lockedUser->save();
                    }
                );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'code' => $exception->getMessage(),
                ]);
        }

        $user = $completedChallenge->user()
            ->firstOrFail();

        Auth::login($user);

        $request->session()->regenerate();

        $this->activityLogger->record(
            action: 'user.registered',
            actor: $user,
            target: $user,
            description: 'Registered and verified a new customer account.',
            metadata: [
                'role' => $user->role,
                'account_status' => $user->account_status,
                'verification_channel' =>
                $completedChallenge->channel,
                'application_flow' =>
                (bool) (
                    $registrationContext['application_flow']
                    ?? false
                ),
            ],
            request: $request
        );

        $isApplicationFlow =
            (bool) (
                $registrationContext['application_flow']
                ?? false
            )
            && $request->session()->has(
                'service_application.coverage'
            )
            && $request->session()->has(
                'service_application.plan_id'
            );

        if ($isApplicationFlow) {
            $request->session()->put(
                'service_application.applicant',
                [
                    'first_name' =>
                    (string) (
                        $registrationContext['first_name']
                        ?? ''
                    ),
                    'last_name' =>
                    (string) (
                        $registrationContext['last_name']
                        ?? ''
                    ),
                    'email' => $user->email,
                ]
            );
        }

        $request->session()->forget([
            'registration.context',
            'registration.verification.challenge',
        ]);

        if ($isApplicationFlow) {
            return redirect()
                ->route('customer.application.create')
                ->with(
                    'success',
                    'Your account has been verified. Complete your Rincomm service application.'
                );
        }

        return redirect()
            ->route('customer.dashboard')
            ->with(
                'success',
                'Your account has been verified successfully. Welcome to Rincomm.'
            );
    }

    /**
     * Send a replacement registration OTP.
     */
    public function resend(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $user = $this->registrationUser(
            $challenge
        );

        $this->assertRegistrationSession(
            $request,
            $challenge
        );

        if (
            $challenge->isVerified()
            || $challenge->isConsumed()
            || $user->account_status !== 'pending_verification'
        ) {
            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'This registration verification request is no longer available.'
                );
        }

        try {
            $issued =
                $this->challengeService->resend(
                    $challenge
                );

            $this->deliveryService->deliver(
                challenge: $issued['challenge'],
                code: $issued['code']
            );
        } catch (DomainException $exception) {
            return back()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        } catch (Throwable $exception) {
            report($exception);

            $this->abandonPendingRegistration(
                $user
            );

            $request->session()->forget([
                'registration.context',
                'registration.verification.challenge',
            ]);

            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'We could not send a new verification code. Please register again.'
                );
        }

        return back()
            ->with(
                'success',
                sprintf(
                    'A new verification code was sent by %s.',
                    strtolower(
                        $this->channelLabel(
                            $issued['challenge']
                        )
                    )
                )
            );
    }

    /**
     * Cancel a still-pending customer registration so the applicant can
     * safely start again with the same email address and mobile number.
     */
    public function cancel(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $user = $this->registrationUser(
            $challenge
        );

        $this->assertRegistrationSession(
            $request,
            $challenge
        );

        if (
            $challenge->isVerified()
            || $challenge->isConsumed()
            || $user->account_status !== 'pending_verification'
        ) {
            $request->session()->forget([
                'registration.context',
                'registration.verification.challenge',
            ]);

            return redirect()
                ->route('register')
                ->with(
                    'error',
                    'This registration verification request is no longer available.'
                );
        }

        $this->abandonPendingRegistration(
            $user
        );

        /*
         * abandonPendingRegistration() deliberately catches cleanup
         * exceptions so delivery failures do not crash registration.
         *
         * For an explicit cancellation, however, never tell the applicant
         * that cancellation succeeded unless the pending account is actually
         * gone.
         */
        if (
            User::query()
                ->whereKey($user->getKey())
                ->exists()
        ) {
            return back()
                ->with(
                    'error',
                    'We could not cancel this registration safely. Please try again.'
                );
        }

        $request->session()->forget([
            'registration.context',
            'registration.verification.challenge',
        ]);

        return redirect()
            ->route('register')
            ->with(
                'success',
                'Registration cancelled. You can start again with your details.'
            );
    }

    /**
     * Ensure the challenge belongs to a pending public customer registration.
     */
    private function registrationUser(
        VerificationChallenge $challenge
    ): User {
        if (
            $challenge->purpose
            !== VerificationChallenge::PURPOSE_REGISTRATION
        ) {
            abort(404);
        }

        $user = $challenge->user()
            ->firstOrFail();

        if ($user->role !== 'customer') {
            abort(404);
        }

        return $user;
    }

    /**
     * Bind a registration challenge to the browser session that created it.
     */
    private function assertRegistrationSession(
        Request $request,
        VerificationChallenge $challenge
    ): void {
        $sessionChallenge = $request->session()->get(
            'registration.verification.challenge'
        );

        if (
            ! is_string($sessionChallenge)
            || $sessionChallenge === ''
            || ! hash_equals(
                $challenge->public_id,
                $sessionChallenge
            )
        ) {
            abort(404);
        }
    }

    /**
     * Present a privacy-safe verification destination.
     */
    private function maskedDestination(
        VerificationChallenge $challenge
    ): string {
        if (
            $challenge->channel
            === VerificationChallenge::CHANNEL_SMS
        ) {
            return $this->phoneNumberService->mask(
                $challenge->destination
            );
        }

        if (
            $challenge->channel
            === VerificationChallenge::CHANNEL_EMAIL
        ) {
            return $this->maskEmail(
                $challenge->destination
            );
        }

        return 'your verified contact';
    }

    /**
     * Human-readable verification channel name.
     */
    private function channelLabel(
        VerificationChallenge $challenge
    ): string {
        return match ($challenge->channel) {
            VerificationChallenge::CHANNEL_EMAIL => 'Email',
            VerificationChallenge::CHANNEL_SMS => 'SMS',
            default => 'Verification service',
        };
    }

    /**
     * Mask an email address while retaining enough context for recognition.
     */
    private function maskEmail(
        string $email
    ): string {
        $email = trim($email);

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return 'your email address';
        }

        [$localPart, $domain] = explode(
            '@',
            $email,
            2
        );

        $visiblePrefix = mb_substr(
            $localPart,
            0,
            1
        );

        return sprintf(
            '%s***@%s',
            $visiblePrefix,
            $domain
        );
    }

    /**
     * Remove a brand-new registration that can no longer receive a usable OTP.
     *
     * The deletion is deliberately limited to a still-unverified customer
     * registration that has not created subscriber/application records.
     */
    private function abandonPendingRegistration(
        User $user
    ): void {
        try {
            DB::transaction(function () use ($user): void {
                $lockedUser = User::query()
                    ->whereKey($user->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedUser instanceof User) {
                    return;
                }

                if (
                    $lockedUser->role !== 'customer'
                    || $lockedUser->account_status
                    !== 'pending_verification'
                    || $lockedUser->activation_completed_at !== null
                    || $lockedUser->customer()->exists()
                    || $lockedUser->technician()->exists()
                    || $lockedUser->serviceApplications()->exists()
                ) {
                    throw new RuntimeException(
                        'Pending registration cleanup was blocked because related account data exists.'
                    );
                }

                $lockedUser->delete();
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
