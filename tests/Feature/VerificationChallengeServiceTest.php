<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\VerificationChallengeService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class VerificationChallengeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_creates_a_hashed_six_digit_pending_challenge(): void
    {
        $user = User::factory()->create();

        $service = app(VerificationChallengeService::class);

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $challenge = $result['challenge'];
        $code = $result['code'];

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $code
        );

        $this->assertSame(
            $user->id,
            $challenge->user_id
        );

        $this->assertSame(
            VerificationChallenge::PURPOSE_REGISTRATION,
            $challenge->purpose
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
            $code,
            $challenge->code_hash
        );

        $this->assertTrue(
            Hash::check(
                $code,
                $challenge->code_hash
            )
        );

        $this->assertSame(
            0,
            $challenge->attempts
        );

        $this->assertSame(
            0,
            $challenge->resend_count
        );

        $this->assertNull(
            $challenge->verified_at
        );

        $this->assertNull(
            $challenge->consumed_at
        );

        $this->assertTrue(
            $challenge->isPending()
        );

        $this->assertFalse(
            $challenge->isVerified()
        );

        $this->assertFalse(
            $challenge->isConsumed()
        );
    }

    public function test_issuing_a_replacement_consumes_the_previous_challenge_for_the_same_purpose(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $first = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $second = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $firstChallenge =
            $first['challenge']->fresh();

        $secondChallenge =
            $second['challenge']->fresh();

        $this->assertNotNull(
            $firstChallenge->consumed_at
        );

        $this->assertTrue(
            $firstChallenge->isConsumed()
        );

        $this->assertNull(
            $secondChallenge->consumed_at
        );

        $this->assertTrue(
            $secondChallenge->isPending()
        );
    }

    public function test_correct_code_verifies_the_challenge(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $verified = $service->verify(
            $result['challenge'],
            $result['code']
        );

        $this->assertNotNull(
            $verified->verified_at
        );

        $this->assertNull(
            $verified->consumed_at
        );

        $this->assertSame(
            0,
            $verified->attempts
        );

        $this->assertTrue(
            $verified->isVerified()
        );

        $this->assertFalse(
            $verified->isConsumed()
        );
    }

    public function test_verified_challenge_cannot_be_verified_again(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $service->verify(
            $result['challenge'],
            $result['code']
        );

        try {
            $service->verify(
                $result['challenge'],
                $result['code']
            );

            $this->fail(
                'An already verified challenge should not verify again.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'This verification challenge has already been verified.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertTrue(
            $challenge->isVerified()
        );

        $this->assertFalse(
            $challenge->isConsumed()
        );

        $this->assertSame(
            0,
            $challenge->attempts
        );
    }

    public function test_wrong_code_persists_failed_attempt_even_when_verification_fails(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $wrongCode =
            $result['code'] === '000000'
            ? '999999'
            : '000000';

        try {
            $service->verify(
                $result['challenge'],
                $wrongCode
            );

            $this->fail(
                'Invalid verification code should have thrown a DomainException.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The verification code is invalid.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

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

    public function test_challenge_blocks_verification_after_maximum_failed_attempts(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $wrongCode =
            $result['code'] === '000000'
            ? '999999'
            : '000000';

        for (
            $attempt = 0;
            $attempt < VerificationChallengeService::MAX_ATTEMPTS;
            $attempt++
        ) {
            try {
                $service->verify(
                    $result['challenge'],
                    $wrongCode
                );

                $this->fail(
                    'Invalid verification code should have thrown a DomainException.'
                );
            } catch (DomainException $exception) {
                $this->assertSame(
                    'The verification code is invalid.',
                    $exception->getMessage()
                );
            }
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertSame(
            VerificationChallengeService::MAX_ATTEMPTS,
            $challenge->attempts
        );

        try {
            $service->verify(
                $challenge,
                $result['code']
            );

            $this->fail(
                'A locked verification challenge should not accept the correct code.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The maximum number of verification attempts has been reached.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            VerificationChallengeService::MAX_ATTEMPTS,
            $challenge->fresh()->attempts
        );
    }

    public function test_expired_code_cannot_be_verified(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $result['challenge']->forceFill([
            'expires_at' => now()->subSecond(),
        ])->save();

        try {
            $service->verify(
                $result['challenge'],
                $result['code']
            );

            $this->fail(
                'An expired verification code should not be accepted.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'This verification code has expired.',
                $exception->getMessage()
            );
        }

        $this->assertNull(
            $result['challenge']
                ->fresh()
                ->verified_at
        );
    }

    public function test_resend_is_blocked_during_the_cooldown_period(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $this->assertGreaterThan(
            0,
            $service->resendAvailableIn(
                $result['challenge']
            )
        );

        try {
            $service->resend(
                $result['challenge']
            );

            $this->fail(
                'Resending during the cooldown should have been rejected.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'Please wait before requesting another verification code.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertSame(
            0,
            $challenge->resend_count
        );
    }

    public function test_resend_after_cooldown_replaces_code_and_resets_attempts(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $originalHash =
            $result['challenge']->code_hash;

        $result['challenge']->forceFill([
            'attempts' => 3,
            'last_sent_at' => now()->subSeconds(
                VerificationChallengeService::RESEND_COOLDOWN_SECONDS + 1
            ),
            'expires_at' => now()->subMinute(),
        ])->save();

        $resent = $service->resend(
            $result['challenge']
        );

        $challenge = $resent['challenge'];

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $resent['code']
        );

        $this->assertNotSame(
            $originalHash,
            $challenge->code_hash
        );

        $this->assertTrue(
            Hash::check(
                $resent['code'],
                $challenge->code_hash
            )
        );

        $this->assertSame(
            0,
            $challenge->attempts
        );

        $this->assertSame(
            1,
            $challenge->resend_count
        );

        $this->assertTrue(
            $challenge->expires_at->isFuture()
        );

        $verified = $service->verify(
            $challenge,
            $resent['code']
        );

        $this->assertTrue(
            $verified->isVerified()
        );
    }

    public function test_resend_is_blocked_after_maximum_resends(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $result['challenge']->forceFill([
            'resend_count' =>
            VerificationChallengeService::MAX_RESENDS,

            'last_sent_at' => now()->subSeconds(
                VerificationChallengeService::RESEND_COOLDOWN_SECONDS + 1
            ),
        ])->save();

        try {
            $service->resend(
                $result['challenge']
            );

            $this->fail(
                'A challenge at its resend limit should not issue another code.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The maximum number of verification-code resends has been reached.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            VerificationChallengeService::MAX_RESENDS,
            $result['challenge']
                ->fresh()
                ->resend_count
        );
    }

    public function test_challenge_must_be_verified_before_it_can_be_consumed(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        try {
            $service->consume(
                $result['challenge']
            );

            $this->fail(
                'An unverified challenge should not be consumable.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The verification challenge must be verified before it can be completed.',
                $exception->getMessage()
            );
        }

        $verified = $service->verify(
            $result['challenge'],
            $result['code']
        );

        $consumed =
            $service->consume($verified);

        $this->assertNotNull(
            $consumed->verified_at
        );

        $this->assertNotNull(
            $consumed->consumed_at
        );

        $this->assertTrue(
            $consumed->isVerified()
        );

        $this->assertTrue(
            $consumed->isConsumed()
        );

        $this->assertFalse(
            $consumed->isPending()
        );
    }

    public function test_consumed_challenge_cannot_be_resent(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $verified = $service->verify(
            $result['challenge'],
            $result['code']
        );

        $consumed =
            $service->consume($verified);

        try {
            $service->resend($consumed);

            $this->fail(
                'A consumed challenge should never be reopened by resend.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'This verification challenge has already been completed.',
                $exception->getMessage()
            );
        }
    }

    public function test_complete_atomically_updates_user_and_consumes_challenge(): void
    {
        $user = User::factory()->create([
            'name' => 'Pending Account',
        ]);

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $completed = $service->complete(
            challenge: $result['challenge'],
            code: $result['code'],
            expectedPurpose: VerificationChallenge::PURPOSE_REGISTRATION,
            completion: function (
                User $lockedUser,
                VerificationChallenge $lockedChallenge
            ): void {
                $lockedUser->forceFill([
                    'name' => 'Verified Account',
                ])->save();

                $this->assertTrue(
                    $lockedChallenge->isVerified()
                );

                $this->assertFalse(
                    $lockedChallenge->isConsumed()
                );
            }
        );

        $this->assertTrue(
            $completed->isVerified()
        );

        $this->assertTrue(
            $completed->isConsumed()
        );

        $this->assertFalse(
            $completed->isPending()
        );

        $this->assertSame(
            'Verified Account',
            $user->fresh()->name
        );
    }

    public function test_complete_persists_invalid_attempt_and_does_not_run_completion(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $wrongCode =
            $result['code'] === '000000'
            ? '999999'
            : '000000';

        $completionCalled = false;

        try {
            $service->complete(
                challenge: $result['challenge'],
                code: $wrongCode,
                expectedPurpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
                completion: function (
                    User $lockedUser,
                    VerificationChallenge $lockedChallenge
                ) use (
                    &$completionCalled
                ): void {
                    $completionCalled = true;
                }
            );

            $this->fail(
                'An invalid completion code should have thrown a DomainException.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'The verification code is invalid.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertFalse(
            $completionCalled
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

    public function test_complete_rejects_wrong_purpose_without_running_completion(): void
    {
        $user = User::factory()->create();

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        $completionCalled = false;

        try {
            $service->complete(
                challenge: $result['challenge'],
                code: $result['code'],
                expectedPurpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
                completion: function (
                    User $lockedUser,
                    VerificationChallenge $lockedChallenge
                ) use (
                    &$completionCalled
                ): void {
                    $completionCalled = true;
                }
            );

            $this->fail(
                'A challenge should not be reusable for a different purpose.'
            );
        } catch (DomainException $exception) {
            $this->assertSame(
                'This verification challenge cannot be used for this action.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertFalse(
            $completionCalled
        );

        $this->assertSame(
            0,
            $challenge->attempts
        );

        $this->assertNull(
            $challenge->verified_at
        );

        $this->assertNull(
            $challenge->consumed_at
        );
    }

    public function test_complete_rolls_back_verification_and_account_changes_when_completion_fails(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Account',
        ]);

        $service = app(
            VerificationChallengeService::class
        );

        $result = $service->issue(
            user: $user,
            purpose: VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            channel: VerificationChallenge::CHANNEL_EMAIL,
            destination: $user->email
        );

        try {
            $service->complete(
                challenge: $result['challenge'],
                code: $result['code'],
                expectedPurpose: VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
                completion: function (
                    User $lockedUser,
                    VerificationChallenge $lockedChallenge
                ): void {
                    $lockedUser->forceFill([
                        'name' => 'Should Roll Back',
                    ])->save();

                    $this->assertTrue(
                        $lockedChallenge->isVerified()
                    );

                    throw new RuntimeException(
                        'Protected account mutation failed.'
                    );
                }
            );

            $this->fail(
                'A failed protected account mutation should have propagated its exception.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Protected account mutation failed.',
                $exception->getMessage()
            );
        }

        $challenge =
            $result['challenge']->fresh();

        $this->assertSame(
            'Original Account',
            $user->fresh()->name
        );

        $this->assertNull(
            $challenge->verified_at
        );

        $this->assertNull(
            $challenge->consumed_at
        );

        $this->assertSame(
            0,
            $challenge->attempts
        );

        $this->assertTrue(
            $challenge->isPending()
        );
    }
}
