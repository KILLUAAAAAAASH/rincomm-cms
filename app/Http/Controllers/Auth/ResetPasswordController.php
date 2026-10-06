<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\ActivityLogger;
use DomainException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    /**
     * Display the new-password form only after successful OTP verification.
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

        if (! $challenge->isVerified()) {
            return redirect()
                ->route(
                    'password.reset.verify',
                    $challenge
                );
        }

        if ($challenge->isExpired()) {
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    'Your verified password recovery session has expired. Please start again.'
                );
        }

        $verifiedSessionChallenge =
            $request->session()->get(
                'password_reset.verified.challenge'
            );

        if (
            ! is_string($verifiedSessionChallenge)
            || $verifiedSessionChallenge === ''
            || ! hash_equals(
                $challenge->public_id,
                $verifiedSessionChallenge
            )
        ) {
            abort(404);
        }

        return view(
            'auth.reset-password',
            [
                'challenge' => $challenge,
            ]
        );
    }

    /**
     * Replace the account password and permanently consume the verified
     * password-reset challenge in one database transaction.
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

        $verifiedSessionChallenge =
            $request->session()->get(
                'password_reset.verified.challenge'
            );

        if (
            ! is_string($verifiedSessionChallenge)
            || $verifiedSessionChallenge === ''
            || ! hash_equals(
                $challenge->public_id,
                $verifiedSessionChallenge
            )
        ) {
            abort(404);
        }

        $validated = $request->validate(
            [
                'password' => [
                    'required',
                    'confirmed',
                    PasswordRule::defaults(),
                ],
            ],
            [
                'password.required' =>
                'Please enter your new password.',
                'password.confirmed' =>
                'The password confirmation does not match.',
            ]
        );

        try {
            $user = DB::transaction(
                function () use (
                    $challenge,
                    $validated,
                    $request
                ): User {
                    $lockedChallenge =
                        VerificationChallenge::query()
                        ->whereKey(
                            $challenge->getKey()
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $lockedChallenge->purpose
                        !== VerificationChallenge::PURPOSE_PASSWORD_RESET
                    ) {
                        throw new DomainException(
                            'This verification challenge cannot be used to reset a password.'
                        );
                    }

                    if ($lockedChallenge->isConsumed()) {
                        throw new DomainException(
                            'This password recovery request has already been completed.'
                        );
                    }

                    if (! $lockedChallenge->isVerified()) {
                        throw new DomainException(
                            'Please verify your password recovery code before choosing a new password.'
                        );
                    }

                    if (
                        $lockedChallenge->expires_at
                        ->lessThanOrEqualTo(now())
                    ) {
                        throw new DomainException(
                            'Your verified password recovery session has expired. Please start again.'
                        );
                    }

                    $lockedUser = User::query()
                        ->whereKey(
                            $lockedChallenge->user_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                     * Password recovery changes credentials only.
                     *
                     * It must never activate, reactivate, approve, or alter
                     * the business/service state of the account.
                     */
                    $lockedUser->forceFill([
                        'password' =>
                        Hash::make(
                            $validated['password']
                        ),
                    ]);

                    /*
                     * Rotate the remember token so persistent authentication
                     * issued before the reset cannot continue using the old
                     * credential state.
                     */
                    $lockedUser->setRememberToken(
                        Str::random(60)
                    );

                    $lockedUser->save();

                    /*
                     * Consume the challenge atomically with the credential
                     * change so it cannot successfully reset the password
                     * twice.
                     */
                    $lockedChallenge->forceFill([
                        'consumed_at' => now(),
                    ])->save();

                    /*
                     * ActivityLogger deliberately converts persistence
                     * failures into null. Password reset auditing is
                     * mandatory, so explicitly convert that null result into
                     * an exception. DB::transaction() will then roll back the
                     * password change and challenge consumption.
                     */
                    $activityLog =
                        $this->activityLogger->record(
                            action: 'user.password_reset',
                            actor: $lockedUser,
                            target: $lockedUser,
                            description: 'Successfully reset the account password using OTP verification.',
                            metadata: [
                                'role' =>
                                $lockedUser->role,
                                'verification_channel' =>
                                $lockedChallenge->channel,
                            ],
                            request: $request
                        );

                    if ($activityLog === null) {
                        throw new RuntimeException(
                            'Unable to persist the password-reset audit record.'
                        );
                    }

                    return $lockedUser->fresh();
                }
            );
        } catch (DomainException $exception) {
            $this->forgetRecoverySession($request);

            return redirect()
                ->route('password.request')
                ->with(
                    'error',
                    $exception->getMessage()
                );
        } catch (Throwable $exception) {
            report($exception);

            /*
             * The transaction has been rolled back. Keep the verified
             * recovery session available so the user can retry without
             * requesting another OTP because of a temporary server failure.
             */
            return back()
                ->with(
                    'error',
                    'We could not complete the password reset. Please try again.'
                );
        }

        /*
         * Credential state and its mandatory audit record are already
         * committed. An unrelated event listener failure must not make the
         * consumed recovery challenge reusable.
         */
        try {
            event(
                new PasswordReset($user)
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        $this->forgetRecoverySession($request);

        /*
         * Rotate the guest session identifier after the sensitive operation.
         */
        $request->session()->regenerate();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your password has been reset successfully. You can now sign in.'
            );
    }

    /**
     * Ensure route-model binding cannot cross verification purposes.
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

        $challenge->user()
            ->firstOrFail();
    }

    /**
     * Ensure the challenge belongs to the browser session that initiated
     * password recovery.
     */
    private function assertRecoverySession(
        Request $request,
        VerificationChallenge $challenge
    ): void {
        $sessionChallenge =
            $request->session()->get(
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
     * Remove all password-recovery authorization from this browser session.
     */
    private function forgetRecoverySession(
        Request $request
    ): void {
        $request->session()->forget([
            'password_reset.verification.challenge',
            'password_reset.verified.challenge',
        ]);
    }
}
