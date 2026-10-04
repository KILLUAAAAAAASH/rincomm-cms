<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\IprogSmsService;
use App\Services\VerificationCodeDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class CustomerRegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_registration_creates_pending_customer_and_sends_otp_without_authenticating(): void
    {
        Mail::fake();

        $response = $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'CUSTOMER@EXAMPLE.COM',
                'phone' => '0917 123 4567',
                'verification_channel' => 'email',
            ])
        );

        $user = User::query()
            ->where('email', 'customer@example.com')
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->where(
                'purpose',
                VerificationChallenge::PURPOSE_REGISTRATION
            )
            ->firstOrFail();

        $response->assertRedirect(
            route('register.verify', $challenge)
        );

        $this->assertGuest();

        $this->assertSame(
            'customer',
            $user->role
        );

        $this->assertSame(
            'pending_verification',
            $user->account_status
        );

        $this->assertSame(
            '639171234567',
            $user->phone
        );

        $this->assertNull(
            $user->email_verified_at
        );

        $this->assertNull(
            $user->phone_verified_at
        );

        $this->assertNull(
            $user->activation_completed_at
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_EMAIL,
            $challenge->channel
        );

        $this->assertSame(
            'customer@example.com',
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

    public function test_sms_registration_normalizes_phone_and_delegates_otp_delivery(): void
    {
        $this->mock(
            IprogSmsService::class,
            function (
                MockInterface $mock
            ): void {
                $mock
                    ->shouldReceive(
                        'sendVerificationCode'
                    )
                    ->once()
                    ->withArgs(
                        function (
                            string $phoneNumber,
                            string $code,
                            string $purpose,
                            int $expiresInMinutes
                        ): bool {
                            return $phoneNumber
                                === '639181234567'
                                && preg_match(
                                    '/^\d{6}$/',
                                    $code
                                ) === 1
                                && $purpose
                                === VerificationChallenge::PURPOSE_REGISTRATION
                                && $expiresInMinutes > 0;
                        }
                    )
                    ->andReturn([
                        'message_id' => 'test-message',
                        'recipient' => '639181234567',
                        'status' => 'Pending',
                    ]);
            }
        );

        $response = $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'sms@example.com',
                'phone' => '+63 918 123 4567',
                'verification_channel' => 'sms',
            ])
        );

        $user = User::query()
            ->where('email', 'sms@example.com')
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $response->assertRedirect(
            route('register.verify', $challenge)
        );

        $this->assertGuest();

        $this->assertSame(
            '639181234567',
            $user->phone
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_SMS,
            $challenge->channel
        );

        $this->assertSame(
            '639181234567',
            $challenge->destination
        );
    }

    public function test_correct_email_otp_activates_customer_and_authenticates_account(): void
    {
        Mail::fake();

        $registrationResponse = $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'verified@example.com',
                'phone' => '09191234567',
                'verification_channel' => 'email',
            ])
        );

        $user = User::query()
            ->where(
                'email',
                'verified@example.com'
            )
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $registrationResponse->assertRedirect(
            route('register.verify', $challenge)
        );

        $code = null;

        Mail::assertSent(
            VerificationCodeMail::class,
            function (
                VerificationCodeMail $mail
            ) use (&$code): bool {
                $code = $mail->code;

                return true;
            }
        );

        $this->assertIsString($code);

        $response = $this->post(
            route(
                'register.verify.store',
                $challenge
            ),
            [
                'code' => $code,
            ]
        );

        $response->assertRedirect(
            route('customer.dashboard')
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $user->refresh();
        $challenge->refresh();

        $this->assertSame(
            'active',
            $user->account_status
        );

        $this->assertNotNull(
            $user->email_verified_at
        );

        $this->assertNull(
            $user->phone_verified_at
        );

        $this->assertNotNull(
            $user->activation_completed_at
        );

        $this->assertNotNull(
            $challenge->verified_at
        );

        $this->assertNotNull(
            $challenge->consumed_at
        );
    }

    public function test_correct_sms_otp_verifies_phone_instead_of_email(): void
    {
        $capturedCode = null;

        $this->mock(
            IprogSmsService::class,
            function (
                MockInterface $mock
            ) use (&$capturedCode): void {
                $mock
                    ->shouldReceive(
                        'sendVerificationCode'
                    )
                    ->once()
                    ->withArgs(
                        function (
                            string $phoneNumber,
                            string $code,
                            string $purpose,
                            int $expiresInMinutes
                        ) use (&$capturedCode): bool {
                            $capturedCode = $code;

                            return $phoneNumber
                                === '639201234567'
                                && $purpose
                                === VerificationChallenge::PURPOSE_REGISTRATION
                                && $expiresInMinutes > 0;
                        }
                    )
                    ->andReturn([
                        'message_id' => 'test-message',
                        'recipient' => '639201234567',
                        'status' => 'Pending',
                    ]);
            }
        );

        $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'smsverify@example.com',
                'phone' => '0920 123 4567',
                'verification_channel' => 'sms',
            ])
        );

        $user = User::query()
            ->where(
                'email',
                'smsverify@example.com'
            )
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertIsString(
            $capturedCode
        );

        $response = $this->post(
            route(
                'register.verify.store',
                $challenge
            ),
            [
                'code' => $capturedCode,
            ]
        );

        $response->assertRedirect(
            route('customer.dashboard')
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $user->refresh();

        $this->assertNull(
            $user->email_verified_at
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertSame(
            'active',
            $user->account_status
        );
    }

    public function test_invalid_otp_increments_attempts_and_keeps_customer_pending_and_guest(): void
    {
        Mail::fake();

        $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'wrongcode@example.com',
                'phone' => '09211234567',
                'verification_channel' => 'email',
            ])
        );

        $user = User::query()
            ->where(
                'email',
                'wrongcode@example.com'
            )
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $response = $this->post(
            route(
                'register.verify.store',
                $challenge
            ),
            [
                'code' => '000000',
            ]
        );

        $response->assertSessionHasErrors(
            'code'
        );

        $this->assertGuest();

        $user->refresh();
        $challenge->refresh();

        $this->assertSame(
            'pending_verification',
            $user->account_status
        );

        $this->assertSame(
            1,
            $challenge->attempts
        );

        $this->assertNull(
            $challenge->verified_at
        );

        $this->assertNull(
            $challenge->consumed_at
        );
    }

    public function test_initial_delivery_failure_removes_brand_new_pending_registration(): void
    {
        $this->mock(
            VerificationCodeDeliveryService::class,
            function (
                MockInterface $mock
            ): void {
                $mock
                    ->shouldReceive('deliver')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated delivery failure.'
                        )
                    );
            }
        );

        $response = $this->from(
            route('register')
        )->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'deliveryfail@example.com',
                'phone' => '09221234567',
                'verification_channel' => 'email',
            ])
        );

        $response->assertRedirect(
            route('register')
        );

        $response->assertSessionHas(
            'error'
        );

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'deliveryfail@example.com',
            ]
        );

        $this->assertDatabaseCount(
            'verification_challenges',
            0
        );
    }

    public function test_registration_verification_challenge_cannot_be_used_from_an_unrelated_session(): void
    {
        Mail::fake();

        $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'sessionbound@example.com',
                'phone' => '09231234567',
                'verification_channel' => 'email',
            ])
        );

        $user = User::query()
            ->where(
                'email',
                'sessionbound@example.com'
            )
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->flushSession();

        $response = $this->get(
            route(
                'register.verify',
                $challenge
            )
        );

        $response->assertNotFound();

        $this->assertGuest();

        $this->assertSame(
            'pending_verification',
            $user->fresh()->account_status
        );
    }

    public function test_pending_registration_can_be_cancelled_and_started_again_with_the_same_details(): void
    {
        Mail::fake();

        $payload = $this->registrationPayload([
            'email' => 'restart@example.com',
            'phone' => '09241234567',
            'verification_channel' => 'email',
        ]);

        $this->post(
            route('register.store'),
            $payload
        );

        $user = User::query()
            ->where('email', 'restart@example.com')
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $response = $this->post(
            route('register.verify.cancel', $challenge)
        );

        $response->assertRedirect(
            route('register')
        );

        $response->assertSessionHas('success');

        $this->assertGuest();

        $this->assertDatabaseMissing(
            'users',
            ['id' => $user->id]
        );

        $this->assertDatabaseMissing(
            'verification_challenges',
            ['id' => $challenge->id]
        );

        $restartResponse = $this->post(
            route('register.store'),
            $payload
        );

        $replacementUser = User::query()
            ->where('email', 'restart@example.com')
            ->firstOrFail();

        $replacementChallenge = VerificationChallenge::query()
            ->where('user_id', $replacementUser->id)
            ->firstOrFail();

        $restartResponse->assertRedirect(
            route('register.verify', $replacementChallenge)
        );

        $this->assertSame(
            'pending_verification',
            $replacementUser->account_status
        );
    }

    public function test_unrelated_session_cannot_cancel_pending_registration(): void
    {
        Mail::fake();

        $this->post(
            route('register.store'),
            $this->registrationPayload([
                'email' => 'cancelbound@example.com',
                'phone' => '09251234567',
                'verification_channel' => 'email',
            ])
        );

        $user = User::query()
            ->where('email', 'cancelbound@example.com')
            ->firstOrFail();

        $challenge = VerificationChallenge::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->flushSession();

        $response = $this->post(
            route('register.verify.cancel', $challenge)
        );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
                'account_status' => 'pending_verification',
            ]
        );

        $this->assertDatabaseHas(
            'verification_challenges',
            ['id' => $challenge->id]
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'juan@example.com',
                'phone' => '09171234567',
                'verification_channel' => 'email',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            $overrides
        );
    }
}
