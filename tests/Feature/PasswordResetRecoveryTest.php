<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\VerificationChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_can_request_email_password_recovery_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'recovery@example.com',
            'phone' => '639171234567',
            'account_status' => 'active',
        ]);

        $response = $this->post(
            route('password.email'),
            [
                'email' => 'RECOVERY@EXAMPLE.COM',
            ]
        );

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where(
                'purpose',
                VerificationChallenge::PURPOSE_PASSWORD_RESET
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'password.reset.verify',
                $challenge
            )
        );

        $response->assertSessionHas(
            'password_reset.verification.challenge',
            $challenge->public_id
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_EMAIL,
            $challenge->channel
        );

        $this->assertSame(
            $user->email,
            $challenge->destination
        );

        $this->assertTrue(
            $challenge->isPending()
        );

        Mail::assertSent(
            VerificationCodeMail::class,
            function (
                VerificationCodeMail $mail
            ) use ($challenge): bool {
                return Hash::check(
                    $mail->code,
                    $challenge->code_hash
                );
            }
        );
    }

    public function test_client_cannot_force_sms_password_recovery_channel(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'email-only@example.com',
            'phone' => '639181234567',
            'account_status' => 'active',
        ]);

        $response = $this->post(
            route('password.email'),
            [
                'email' => $user->email,
                'verification_channel' =>
                VerificationChallenge::CHANNEL_SMS,
            ]
        );

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where(
                'purpose',
                VerificationChallenge::PURPOSE_PASSWORD_RESET
            )
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'password.reset.verify',
                $challenge
            )
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_EMAIL,
            $challenge->channel
        );

        $this->assertSame(
            $user->email,
            $challenge->destination
        );

        $this->assertNotSame(
            $user->phone,
            $challenge->destination
        );

        Mail::assertSent(
            VerificationCodeMail::class
        );
    }

    public function test_unknown_email_receives_neutral_response_without_creating_challenge(): void
    {
        Mail::fake();

        $response = $this->post(
            route('password.email'),
            [
                'email' => 'missing@example.com',
            ]
        );

        $response->assertSessionHas(
            'status',
            'If an eligible Rincomm account matches that email address, a verification code will be sent.'
        );

        $this->assertDatabaseCount(
            'verification_challenges',
            0
        );

        Mail::assertNothingSent();
    }

    public function test_pending_verification_account_receives_neutral_response_without_creating_challenge(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'account_status' => 'pending_verification',
            'activation_completed_at' => null,
        ]);

        $response = $this->post(
            route('password.email'),
            [
                'email' => $user->email,
            ]
        );

        $response->assertSessionHas(
            'status',
            'If an eligible Rincomm account matches that email address, a verification code will be sent.'
        );

        $this->assertDatabaseMissing(
            'verification_challenges',
            [
                'user_id' => $user->id,
                'purpose' =>
                VerificationChallenge::PURPOSE_PASSWORD_RESET,
            ]
        );

        Mail::assertNothingSent();
    }

    public function test_forgot_password_requires_valid_email_address(): void
    {
        Mail::fake();

        $response = $this->post(
            route('password.email'),
            [
                'email' => 'not-an-email',
            ]
        );

        $response->assertSessionHasErrors(
            'email'
        );

        $this->assertDatabaseCount(
            'verification_challenges',
            0
        );

        Mail::assertNothingSent();
    }

    public function test_password_reset_verification_page_requires_matching_browser_session(): void
    {
        $user = User::factory()->create([
            'email' => 'session-check@example.com',
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $response = $this->get(
            route(
                'password.reset.verify',
                $issued['challenge']
            )
        );

        $response->assertNotFound();
    }

    public function test_incorrect_otp_does_not_verify_password_recovery_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong-code@example.com',
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $wrongCode =
            $issued['code'] === '000000'
            ? '999999'
            : '000000';

        $response = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.reset.verify.store',
                    $issued['challenge']
                ),
                [
                    'code' => $wrongCode,
                ]
            );

        $response->assertSessionHasErrors(
            'code'
        );

        $issued['challenge']->refresh();

        $this->assertNull(
            $issued['challenge']->verified_at
        );

        $this->assertSame(
            1,
            $issued['challenge']->attempts
        );
    }

    public function test_expired_otp_cannot_verify_password_recovery(): void
    {
        $user = User::factory()->create([
            'email' => 'expired-code@example.com',
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $issued['challenge']->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $response = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.reset.verify.store',
                    $issued['challenge']
                ),
                [
                    'code' => $issued['code'],
                ]
            );

        $response->assertSessionHasErrors(
            'code'
        );

        $issued['challenge']->refresh();

        $this->assertNull(
            $issued['challenge']->verified_at
        );
    }

    public function test_correct_otp_authorizes_new_password_form(): void
    {
        $user = User::factory()->create([
            'email' => 'verified-reset@example.com',
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $verificationResponse = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.reset.verify.store',
                    $issued['challenge']
                ),
                [
                    'code' => $issued['code'],
                ]
            );

        $verificationResponse->assertRedirect(
            route(
                'password.reset',
                $issued['challenge']
            )
        );

        $verificationResponse->assertSessionHas(
            'password_reset.verified.challenge',
            $issued['challenge']->public_id
        );

        $issued['challenge']->refresh();

        $this->assertNotNull(
            $issued['challenge']->verified_at
        );

        $this->assertNull(
            $issued['challenge']->consumed_at
        );

        $resetPageResponse = $this->get(
            route(
                'password.reset',
                $issued['challenge']
            )
        );

        $resetPageResponse->assertOk();

        $resetPageResponse->assertViewIs(
            'auth.reset-password'
        );

        $resetPageResponse->assertViewHas(
            'challenge',
            fn(
                VerificationChallenge $challenge
            ): bool =>
            $challenge->is(
                $issued['challenge']
            )
        );
    }

    public function test_unverified_challenge_cannot_access_new_password_form(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified-reset@example.com',
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $response = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->get(
                route(
                    'password.reset',
                    $issued['challenge']
                )
            );

        $response->assertRedirect(
            route(
                'password.reset.verify',
                $issued['challenge']
            )
        );
    }

    public function test_verified_otp_changes_password_consumes_challenge_and_preserves_account_status(): void
    {
        $oldRememberToken =
            str_repeat('a', 60);

        $user = User::factory()->create([
            'email' => 'complete-reset@example.com',
            'password' => Hash::make(
                'OldSecurePassword123!'
            ),
            'remember_token' =>
            $oldRememberToken,
            'account_status' =>
            'deactivated',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $verificationResponse = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.reset.verify.store',
                    $issued['challenge']
                ),
                [
                    'code' => $issued['code'],
                ]
            );

        $verificationResponse->assertRedirect(
            route(
                'password.reset',
                $issued['challenge']
            )
        );

        $resetResponse = $this->post(
            route(
                'password.update',
                $issued['challenge']
            ),
            [
                'password' =>
                'NewSecurePassword123!',
                'password_confirmation' =>
                'NewSecurePassword123!',
            ]
        );

        $resetResponse->assertRedirect(
            route('login')
        );

        $resetResponse->assertSessionHas(
            'status'
        );

        $resetResponse->assertSessionMissing(
            'password_reset.verification.challenge'
        );

        $resetResponse->assertSessionMissing(
            'password_reset.verified.challenge'
        );

        $user->refresh();

        $issued['challenge']->refresh();

        $this->assertTrue(
            Hash::check(
                'NewSecurePassword123!',
                $user->password
            )
        );

        $this->assertSame(
            'deactivated',
            $user->account_status
        );

        $this->assertNotSame(
            $oldRememberToken,
            $user->getRememberToken()
        );

        $this->assertNotNull(
            $issued['challenge']->consumed_at
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_EMAIL,
            $issued['challenge']->channel
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'actor_user_id' =>
                $user->id,
                'target_user_id' =>
                $user->id,
                'action' =>
                'user.password_reset',
            ]
        );
    }

    public function test_password_confirmation_failure_does_not_change_password_or_consume_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'confirmation@example.com',
            'password' => Hash::make(
                'OldSecurePassword123!'
            ),
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $issued['challenge']->forceFill([
            'verified_at' => now(),
        ])->save();

        $response = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
                'password_reset.verified.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.update',
                    $issued['challenge']
                ),
                [
                    'password' =>
                    'NewSecurePassword123!',
                    'password_confirmation' =>
                    'DifferentSecurePassword123!',
                ]
            );

        $response->assertSessionHasErrors(
            'password'
        );

        $user->refresh();

        $issued['challenge']->refresh();

        $this->assertTrue(
            Hash::check(
                'OldSecurePassword123!',
                $user->password
            )
        );

        $this->assertNull(
            $issued['challenge']->consumed_at
        );
    }

    public function test_consumed_password_reset_challenge_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'email' => 'single-use@example.com',
            'password' => Hash::make(
                'OldSecurePassword123!'
            ),
            'account_status' => 'active',
        ]);

        $issued =
            $this->issuePasswordResetChallenge(
                $user
            );

        $issued['challenge']->forceFill([
            'verified_at' => now(),
        ])->save();

        $firstResponse = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
                'password_reset.verified.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.update',
                    $issued['challenge']
                ),
                [
                    'password' =>
                    'FirstNewPassword123!',
                    'password_confirmation' =>
                    'FirstNewPassword123!',
                ]
            );

        $firstResponse->assertRedirect(
            route('login')
        );

        $issued['challenge']->refresh();

        $this->assertNotNull(
            $issued['challenge']->consumed_at
        );

        $secondResponse = $this
            ->withSession([
                'password_reset.verification.challenge' =>
                $issued['challenge']->public_id,
                'password_reset.verified.challenge' =>
                $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'password.update',
                    $issued['challenge']
                ),
                [
                    'password' =>
                    'SecondNewPassword123!',
                    'password_confirmation' =>
                    'SecondNewPassword123!',
                ]
            );

        $secondResponse->assertRedirect(
            route('password.request')
        );

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'FirstNewPassword123!',
                $user->password
            )
        );

        $this->assertFalse(
            Hash::check(
                'SecondNewPassword123!',
                $user->password
            )
        );
    }

    /**
     * @return array{
     *     challenge: VerificationChallenge,
     *     code: string
     * }
     */
    private function issuePasswordResetChallenge(
        User $user
    ): array {
        return app(
            VerificationChallengeService::class
        )->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: (string) $user->email
        );
    }
}
