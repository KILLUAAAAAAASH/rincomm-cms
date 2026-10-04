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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class EmployeeActivationController extends Controller
{
    public function __construct(
        private readonly VerificationChallengeService $challengeService,
        private readonly VerificationCodeDeliveryService $deliveryService,
        private readonly PhilippinePhoneNumberService $phoneNumberService,
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Let a credential-verified employee choose where to receive the OTP.
     */
    public function channel(
        Request $request
    ): View|RedirectResponse {
        $user = $this->activationEmployee(
            $request
        );

        if (
            $user->account_status !== 'pending_verification'
            || $user->activation_completed_at !== null
        ) {
            $request->session()->forget([
                'employee.activation.user_id',
                'employee.activation.challenge',
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This employee account is no longer awaiting activation.'
                );
        }

        return view(
            'auth.employee-activation-channel',
            [
                'employee' => $user,

                'maskedEmail' =>
                    $this->maskEmail(
                        (string) $user->email
                    ),

                'maskedPhone' =>
                    trim((string) $user->phone) !== ''
                        ? $this->phoneNumberService->mask(
                            (string) $user->phone
                        )
                        : 'No mobile number assigned',

                'emailAvailable' =>
                    filter_var(
                        (string) $user->email,
                        FILTER_VALIDATE_EMAIL
                    ) !== false,

                'smsAvailable' =>
                    trim(
                        (string) $user->phone
                    ) !== '',
            ]
        );
    }

    /**
     * Issue and deliver the employee activation OTP using the selected channel.
     */
    public function send(
        Request $request
    ): RedirectResponse {
        $user = $this->activationEmployee(
            $request
        );

        if (
            $user->account_status !== 'pending_verification'
            || $user->activation_completed_at !== null
        ) {
            $request->session()->forget([
                'employee.activation.user_id',
                'employee.activation.challenge',
            ]);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This employee account is no longer awaiting activation.'
                );
        }

        $validated = $request->validate(
            [
                'verification_channel' => [
                    'required',
                    Rule::in([
                        VerificationChallenge::CHANNEL_EMAIL,
                        VerificationChallenge::CHANNEL_SMS,
                    ]),
                ],
            ],
            [
                'verification_channel.required' =>
                    'Please choose where to receive your verification code.',

                'verification_channel.in' =>
                    'Please choose a valid verification method.',
            ]
        );

        $channel =
            $validated['verification_channel'];

        if (
            $channel
            === VerificationChallenge::CHANNEL_EMAIL
        ) {
            $destination = mb_strtolower(
                trim(
                    (string) $user->email
                )
            );

            if (
                filter_var(
                    $destination,
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'verification_channel' =>
                            'No valid email address is assigned to this employee account.',
                    ]);
            }
        } else {
            $destination = trim(
                (string) $user->phone
            );

            if ($destination === '') {
                return back()
                    ->withInput()
                    ->withErrors([
                        'verification_channel' =>
                            'No mobile number is assigned to this employee account.',
                    ]);
            }
        }

        try {
            $issued =
                $this->challengeService->issue(
                    user: $user,
                    purpose:
                        VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
                    channel: $channel,
                    destination: $destination
                );

            $this->deliveryService->deliver(
                challenge:
                    $issued['challenge'],
                code:
                    $issued['code']
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'We could not send your verification code. Please try again or choose another verification method.'
                );
        }

        $request->session()->put(
            'employee.activation.challenge',
            $issued['challenge']->public_id
        );

        return redirect()
            ->route(
                'employee.activation',
                $issued['challenge']
            );
    }

    /**
     * Display the first-time employee activation page.
     */
    public function create(
        Request $request,
        VerificationChallenge $challenge
    ): View|RedirectResponse {
        $user = $this->employeeUser(
            $challenge
        );

        $this->assertActivationSession(
            $request,
            $challenge
        );

        if (
            $challenge->isVerified()
            || $challenge->isConsumed()
            || $user->account_status !== 'pending_verification'
            || $user->activation_completed_at !== null
        ) {
            $request->session()->forget(
                'employee.activation.challenge'
            );

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This employee activation request is no longer available.'
                );
        }

        return view(
            'auth.employee-activation',
            [
                'challenge' => $challenge,
                'employee' => $user,

                'maskedDestination' =>
                    $this->maskedDestination(
                        $challenge
                    ),

                'channelLabel' =>
                    $this->channelLabel(
                        $challenge
                    ),

                'isExpired' =>
                    $challenge->isExpired(),

                'resendAvailableIn' =>
                    $this->challengeService
                        ->resendAvailableIn(
                            $challenge
                        ),

                'remainingAttempts' =>
                    max(
                        0,
                        VerificationChallengeService::MAX_ATTEMPTS
                            - $challenge->attempts
                    ),
            ]
        );
    }

    /**
     * Verify the employee OTP, replace the default password,
     * and activate the account atomically.
     */
    public function store(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $this->employeeUser(
            $challenge
        );

        $this->assertActivationSession(
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

                'password' => [
                    'bail',
                    'required',
                    'string',
                    'confirmed',
                    'not_in:12345678',
                    Password::min(8),
                ],
            ],
            [
                'code.required' =>
                    'Please enter the six-digit verification code.',

                'code.regex' =>
                    'The verification code must contain exactly six digits.',

                'password.required' =>
                    'Please create your permanent password.',

                'password.confirmed' =>
                    'Password confirmation does not match.',

                'password.not_in' =>
                    'Your new password must be different from the default password.',

                'password.min' =>
                    'Your password must contain at least 8 characters.',
            ]
        );

        try {
            $completedChallenge =
                $this->challengeService->complete(
                    challenge: $challenge,
                    code: $validated['code'],
                    expectedPurpose:
                        VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,

                    completion: function (
                        User $lockedUser,
                        VerificationChallenge $lockedChallenge
                    ) use ($validated): void {
                        if (
                            ! in_array(
                                $lockedUser->role,
                                [
                                    'staff',
                                    'technician',
                                ],
                                true
                            )
                            || $lockedUser->account_status
                                !== 'pending_verification'
                            || $lockedUser->activation_completed_at !== null
                        ) {
                            throw new DomainException(
                                'This account cannot be activated through employee activation.'
                            );
                        }

                        if (
                            $lockedChallenge->channel
                            === VerificationChallenge::CHANNEL_EMAIL
                        ) {
                            if (
                                mb_strtolower(
                                    trim(
                                        (string) $lockedUser->email
                                    )
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

                            $lockedUser->email_verified_at =
                                now();
                        } elseif (
                            $lockedChallenge->channel
                            === VerificationChallenge::CHANNEL_SMS
                        ) {
                            if (
                                trim(
                                    (string) $lockedUser->phone
                                )
                                !== trim(
                                    $lockedChallenge->destination
                                )
                            ) {
                                throw new DomainException(
                                    'The verification mobile number no longer matches this account.'
                                );
                            }

                            $lockedUser->phone_verified_at =
                                now();
                        } else {
                            throw new DomainException(
                                'The verification channel is invalid.'
                            );
                        }

                        $lockedUser->password =
                            $validated['password'];

                        $lockedUser->account_status =
                            'active';

                        $lockedUser->activation_completed_at =
                            now();

                        $lockedUser->save();
                    }
                );
        } catch (DomainException $exception) {
            return back()
                ->withInput(
                    $request->except([
                        'password',
                        'password_confirmation',
                    ])
                )
                ->withErrors([
                    'code' =>
                        $exception->getMessage(),
                ]);
        }

        $user = $completedChallenge
            ->user()
            ->firstOrFail();

        Auth::login($user);

        $request->session()->regenerate();

        $request->session()->forget([
            'employee.activation.user_id',
            'employee.activation.challenge',
        ]);

        $this->activityLogger->record(
            action:
                'user.employee_activation_completed',

            actor: $user,
            target: $user,

            description:
                'Completed first-time employee account activation.',

            metadata: [
                'role' =>
                    $user->role,

                'employee_number' =>
                    $user->employee_number,

                'verification_channel' =>
                    $completedChallenge->channel,

                'account_status' =>
                    $user->account_status,
            ],

            request: $request
        );

        $route =
            $user->role === 'technician'
                ? 'technician.dashboard'
                : 'dashboard';

        return redirect()
            ->route($route)
            ->with(
                'success',
                'Your employee account has been activated successfully.'
            );
    }

    /**
     * Send a replacement employee activation OTP through the same channel.
     */
    public function resend(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $user =
            $this->employeeUser(
                $challenge
            );

        $this->assertActivationSession(
            $request,
            $challenge
        );

        if (
            $challenge->isVerified()
            || $challenge->isConsumed()
            || $user->account_status !== 'pending_verification'
            || $user->activation_completed_at !== null
        ) {
            $request->session()->forget(
                'employee.activation.challenge'
            );

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'This employee activation request is no longer available.'
                );
        }

        try {
            $issued =
                $this->challengeService->resend(
                    $challenge
                );

            $this->deliveryService->deliver(
                challenge:
                    $issued['challenge'],
                code:
                    $issued['code']
            );
        } catch (DomainException $exception) {
            return back()
                ->with(
                    'error',
                    $exception->getMessage()
                );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->with(
                    'error',
                    'We could not send a new verification code. Please try again.'
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
     * Resolve the employee whose credentials started this activation session.
     */
    private function activationEmployee(
        Request $request
    ): User {
        $userId =
            $request->session()->get(
                'employee.activation.user_id'
            );

        if (
            ! is_numeric($userId)
        ) {
            abort(404);
        }

        $user = User::query()
            ->whereKey($userId)
            ->whereIn(
                'role',
                [
                    'staff',
                    'technician',
                ]
            )
            ->first();

        if (! $user instanceof User) {
            abort(404);
        }

        return $user;
    }

    /**
     * Ensure the challenge belongs to an employee activation flow.
     */
    private function employeeUser(
        VerificationChallenge $challenge
    ): User {
        if (
            $challenge->purpose
            !== VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION
        ) {
            abort(404);
        }

        if (
            ! in_array(
                $challenge->channel,
                [
                    VerificationChallenge::CHANNEL_EMAIL,
                    VerificationChallenge::CHANNEL_SMS,
                ],
                true
            )
        ) {
            abort(404);
        }

        $user = $challenge
            ->user()
            ->firstOrFail();

        if (
            ! in_array(
                $user->role,
                [
                    'staff',
                    'technician',
                ],
                true
            )
        ) {
            abort(404);
        }

        return $user;
    }

    /**
     * Bind the activation challenge to the browser session that started it.
     */
    private function assertActivationSession(
        Request $request,
        VerificationChallenge $challenge
    ): void {
        $sessionChallenge =
            $request->session()->get(
                'employee.activation.challenge'
            );

        if (
            ! is_string(
                $sessionChallenge
            )
            || $sessionChallenge === ''
            || ! hash_equals(
                $challenge->public_id,
                $sessionChallenge
            )
        ) {
            abort(404);
        }
    }

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

    private function channelLabel(
        VerificationChallenge $challenge
    ): string {
        return match (
            $challenge->channel
        ) {
            VerificationChallenge::CHANNEL_EMAIL =>
                'Email',

            VerificationChallenge::CHANNEL_SMS =>
                'SMS',

            default =>
                'Verification service',
        };
    }

    private function maskEmail(
        string $email
    ): string {
        $email = trim(
            $email
        );

        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return 'No valid email address assigned';
        }

        [
            $localPart,
            $domain,
        ] = explode(
            '@',
            $email,
            2
        );

        return sprintf(
            '%s***@%s',
            mb_substr(
                $localPart,
                0,
                1
            ),
            $domain
        );
    }
}
