<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\VerificationChallenge;
use App\Services\PhilippinePhoneNumberService;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PasswordResetVerificationController extends Controller
{
    public function __construct(
        private readonly VerificationChallengeService $challengeService,
        private readonly VerificationCodeDeliveryService $deliveryService,
        private readonly PhilippinePhoneNumberService $phoneNumberService
    ) {}

    /**
     * Display the password-reset OTP verification page.
     */
    public function create(
        Request $request,
        VerificationChallenge $challenge
    ): View|RedirectResponse {
        $this->passwordResetChallenge($challenge);

        $this->assertRecoverySession(
            $request,
            $challenge
        );

        if ($challenge->isConsumed()) {
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'This password recovery request is no longer available.'
                );
        }

        /*
         * Once the OTP has been verified, send the user directly to the
         * new-password form instead of asking for the same code again.
         */
        if ($challenge->isVerified()) {
            if ($challenge->isExpired()) {
                $this->forgetRecoverySession($request);

                return redirect()
                    ->route('password.request')
                    ->with(
                        'error',
                        'Your verified password recovery session has expired. Please start again.'
                    );
            }

            $request->session()->put(
                'password_reset.verified.challenge',
                $challenge->public_id
            );

            return redirect()
                ->route(
                    'password.reset',
                    $challenge
                );
        }

        return view('auth.verify-password-reset', [
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
     * Verify the password-reset OTP.
     */
    public function store(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $this->passwordResetChallenge($challenge);

        $this->assertRecoverySession(
            $request,
            $challenge
        );

        if ($challenge->isConsumed()) {
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'This password recovery request is no longer available.'
                );
        }

        if ($challenge->isVerified()) {
            if ($challenge->isExpired()) {
                $this->forgetRecoverySession($request);

                return redirect()
                    ->route('password.request')
                    ->with(
                        'error',
                        'Your verified password recovery session has expired. Please start again.'
                    );
            }

            $request->session()->put(
                'password_reset.verified.challenge',
                $challenge->public_id
            );

            return redirect()
                ->route(
                    'password.reset',
                    $challenge
                );
        }

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

        try {
            $verifiedChallenge =
                $this->challengeService->verify(
                    challenge: $challenge,
                    code: $validated['code']
                );
        } catch (DomainException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'code' => $exception->getMessage(),
                ]);
        }

        /*
         * Rotate the guest session identifier after successful account
         * ownership verification while preserving the recovery context.
         */
        $request->session()->regenerate();

        $request->session()->put(
            'password_reset.verification.challenge',
            $verifiedChallenge->public_id
        );

        $request->session()->put(
            'password_reset.verified.challenge',
            $verifiedChallenge->public_id
        );

        return redirect()
            ->route(
                'password.reset',
                $verifiedChallenge
            )
            ->with(
                'success',
                'Verification successful. You can now choose a new password.'
            );
    }

    /**
     * Send a replacement password-reset OTP.
     */
    public function resend(
        Request $request,
        VerificationChallenge $challenge
    ): RedirectResponse {
        $this->passwordResetChallenge($challenge);

        $this->assertRecoverySession(
            $request,
            $challenge
        );

        if ($challenge->isConsumed()) {
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'This password recovery request is no longer available.'
                );
        }

        if ($challenge->isVerified()) {
            if ($challenge->isExpired()) {
                $this->forgetRecoverySession($request);

                return redirect()
                    ->route('password.request')
                    ->with(
                        'error',
                        'Your verified password recovery session has expired. Please start again.'
                    );
            }

            $request->session()->put(
                'password_reset.verified.challenge',
                $challenge->public_id
            );

            return redirect()
                ->route(
                    'password.reset',
                    $challenge
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

            /*
             * Delivery failures revoke the challenge. Clear the browser
             * recovery authorization as well.
             */
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'We could not send a new verification code. Please start password recovery again.'
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
     * Ensure this challenge belongs to password recovery.
     */
    private function passwordResetChallenge(
        VerificationChallenge $challenge
    ): void {
        if (
            $challenge->purpose
            !== VerificationChallenge::PURPOSE_PASSWORD_RESET
        ) {
            abort(404);
        }

        /*
         * Ensure the related account still exists.
         */
        $challenge->user()
            ->firstOrFail();
    }

    /**
     * Bind the challenge to the browser session that started recovery.
     */
    private function assertRecoverySession(
        Request $request,
        VerificationChallenge $challenge
    ): void {
        $sessionChallenge = $request->session()->get(
            'password_reset.verification.challenge'
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
     * Remove password-recovery authorization from the browser session.
     */
    private function forgetRecoverySession(
        Request $request
    ): void {
        $request->session()->forget([
            'password_reset.verification.challenge',
            'password_reset.verified.challenge',
        ]);
    }

    /**
     * Present a privacy-safe destination.
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

        return 'your registered contact';
    }

    /**
     * Human-readable delivery channel.
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
     * Mask an email address while keeping it recognizable.
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
}
