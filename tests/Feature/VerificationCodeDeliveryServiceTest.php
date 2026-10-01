<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\SemaphoreSmsService;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class VerificationCodeDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_challenge_is_delivered_through_laravel_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        $deliveryService->deliver(
            $issued['challenge'],
            $issued['code']
        );

        Mail::assertSent(
            VerificationCodeMail::class,
            function (VerificationCodeMail $mail) use (
                $user,
                $issued
            ): bool {
                return $mail->hasTo($user->email)
                    && $mail->code === $issued['code']
                    && $mail->purpose
                    === VerificationChallenge::PURPOSE_REGISTRATION
                    && $mail->expiresInMinutes >= 1
                    && $mail->expiresInMinutes
                    <= VerificationChallengeService::EXPIRY_MINUTES;
            }
        );

        $challenge = $issued['challenge']->fresh();

        $this->assertTrue($challenge->isPending());
        $this->assertNull($challenge->verified_at);
        $this->assertNull($challenge->consumed_at);
    }

    public function test_sms_challenge_is_delegated_to_semaphore_service(): void
    {
        $user = User::factory()->create();

        $phoneNumber = '639171234567';

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            channel: VerificationChallenge::CHANNEL_SMS,
            destination: $phoneNumber
        );

        $semaphore = Mockery::mock(
            SemaphoreSmsService::class
        );

        $semaphore
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with(
                $phoneNumber,
                $issued['code'],
                VerificationChallenge::PURPOSE_PASSWORD_RESET,
                Mockery::on(
                    fn($minutes): bool =>
                    is_int($minutes)
                        && $minutes >= 1
                        && $minutes
                        <= VerificationChallengeService::EXPIRY_MINUTES
                )
            )
            ->andReturn([
                'message_id' => 1001,
                'recipient' => $phoneNumber,
                'status' => 'Pending',
            ]);

        $this->app->instance(
            SemaphoreSmsService::class,
            $semaphore
        );

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        $deliveryService->deliver(
            $issued['challenge'],
            $issued['code']
        );

        $challenge = $issued['challenge']->fresh();

        $this->assertTrue($challenge->isPending());
        $this->assertNull($challenge->verified_at);
        $this->assertNull($challenge->consumed_at);
    }

    public function test_mismatched_plaintext_code_revokes_the_challenge(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $wrongCode = $issued['code'] === '000000'
            ? '999999'
            : '000000';

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        try {
            $deliveryService->deliver(
                $issued['challenge'],
                $wrongCode
            );

            $this->fail(
                'A mismatched delivery code should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The verification code does not match the challenge.',
                $exception->getMessage()
            );
        }

        Mail::assertNothingSent();

        $challenge = $issued['challenge']->fresh();

        $this->assertFalse($challenge->isPending());
    }

    public function test_invalid_email_destination_revokes_the_challenge(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: 'not-an-email-address'
        );

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        try {
            $deliveryService->deliver(
                $issued['challenge'],
                $issued['code']
            );

            $this->fail(
                'An invalid email destination should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The verification email address is invalid.',
                $exception->getMessage()
            );
        }

        Mail::assertNothingSent();

        $challenge = $issued['challenge']->fresh();

        $this->assertFalse($challenge->isPending());
    }

    public function test_sms_delivery_failure_revokes_the_challenge(): void
    {
        $user = User::factory()->create();

        $phoneNumber = '639181234567';

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            channel: VerificationChallenge::CHANNEL_SMS,
            destination: $phoneNumber
        );

        $semaphore = Mockery::mock(
            SemaphoreSmsService::class
        );

        $semaphore
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Semaphore verification SMS delivery failed.'
                )
            );

        $this->app->instance(
            SemaphoreSmsService::class,
            $semaphore
        );

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        try {
            $deliveryService->deliver(
                $issued['challenge'],
                $issued['code']
            );

            $this->fail(
                'A failed SMS delivery should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Semaphore verification SMS delivery failed.',
                $exception->getMessage()
            );
        }

        $challenge = $issued['challenge']->fresh();

        $this->assertFalse($challenge->isPending());
    }

    public function test_unsupported_delivery_channel_revokes_the_challenge(): void
    {
        $user = User::factory()->create();

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $issued['challenge']->forceFill([
            'channel' => 'unsupported',
        ])->save();

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        try {
            $deliveryService->deliver(
                $issued['challenge'],
                $issued['code']
            );

            $this->fail(
                'An unsupported delivery channel should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Unsupported verification delivery channel.',
                $exception->getMessage()
            );
        }

        $challenge = $issued['challenge']->fresh();

        $this->assertFalse($challenge->isPending());
    }

    public function test_completed_challenge_cannot_be_delivered_again(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $challengeService = app(
            VerificationChallengeService::class
        );

        $issued = $challengeService->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $verified = $challengeService->verify(
            $issued['challenge'],
            $issued['code']
        );

        $challengeService->consume($verified);

        $deliveryService = app(
            VerificationCodeDeliveryService::class
        );

        try {
            $deliveryService->deliver(
                $issued['challenge'],
                $issued['code']
            );

            $this->fail(
                'A completed challenge should not be delivered again.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Only pending verification challenges can be delivered.',
                $exception->getMessage()
            );
        }

        Mail::assertNothingSent();

        $challenge = $issued['challenge']->fresh();

        $this->assertTrue($challenge->isConsumed());
        $this->assertFalse($challenge->isPending());
    }
}
