<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly VerificationChallengeService $challengeService,
        private readonly VerificationCodeDeliveryService $deliveryService
    ) {}

    /**
     * Display the forgot-password form.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Start password recovery through Email OTP.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $email = Str::lower(
            trim(
                (string) $request->input(
                    'email',
                    ''
                )
            )
        );

        $request->merge([
            'email' => $email,
        ]);

        $validated = $request->validate(
            [
                'email' => [
                    'bail',
                    'required',
                    'email',
                    'max:255',
                ],
            ],
            [
                'email.required' =>
                'Please enter your account email address.',
                'email.email' =>
                'Please enter a valid email address.',
            ]
        );

        /*
         * Use the same public response whenever recovery cannot begin.
         * This avoids revealing whether an email exists or whether the
         * associated account is still awaiting verification.
         */
        $neutralStatus =
            'If an eligible Rincomm account matches that email address, a verification code will be sent.';

        /*
         * Clear any previous password-recovery authorization before
         * beginning another recovery attempt.
         */
        $request->session()->forget([
            'password_reset.verification.challenge',
            'password_reset.verified.challenge',
        ]);

        $user = User::query()
            ->where(
                'email',
                $validated['email']
            )
            ->first();

        /*
         * Pending-verification accounts must finish their existing
         * registration or employee-activation workflow instead of
         * using password recovery.
         *
         * Keep the public response neutral to prevent account-state
         * enumeration.
         */
        if (
            ! $user instanceof User
            || $user->account_status === 'pending_verification'
        ) {
            return back()
                ->withInput([
                    'email' => $validated['email'],
                ])
                ->with(
                    'status',
                    $neutralStatus
                );
        }

        $destination = trim(
            (string) $user->email
        );

        if ($destination === '') {
            return back()
                ->withInput([
                    'email' => $validated['email'],
                ])
                ->with(
                    'status',
                    $neutralStatus
                );
        }

        try {
            $issued =
                $this->challengeService->issue(
                    user: $user,
                    purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
                    channel: VerificationChallenge::CHANNEL_EMAIL,
                    destination: $destination
                );

            $this->deliveryService->deliver(
                challenge: $issued['challenge'],
                code: $issued['code']
            );
        } catch (Throwable $exception) {
            report($exception);

            $request->session()->forget([
                'password_reset.verification.challenge',
                'password_reset.verified.challenge',
            ]);

            return back()
                ->withInput([
                    'email' => $validated['email'],
                ])
                ->with(
                    'error',
                    'We could not send your verification code. Please try again.'
                );
        }

        /*
         * Rotate the session identifier before binding this browser
         * to the newly issued password-reset challenge.
         */
        $request->session()->regenerate();

        $request->session()->put(
            'password_reset.verification.challenge',
            $issued['challenge']->public_id
        );

        $request->session()->forget(
            'password_reset.verified.challenge'
        );

        return redirect()
            ->route(
                'password.reset.verify',
                $issued['challenge']
            )
            ->with(
                'success',
                'We sent a six-digit password recovery code to your registered email address.'
            );
    }
}
