<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationChallenge;
use Closure;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VerificationChallengeService
{
    public const CODE_LENGTH = 6;

    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_RESENDS = 5;

    /**
     * Issue a new verification challenge.
     *
     * The returned plaintext code exists only in memory so that the delivery
     * layer can send it. Only its one-way hash is persisted.
     *
     * @return array{
     *     challenge: VerificationChallenge,
     *     code: string
     * }
     */
    public function issue(
        User $user,
        string $purpose,
        string $channel,
        string $destination
    ): array {
        $this->assertSupportedPurpose($purpose);
        $this->assertSupportedChannel($channel);

        $destination = trim($destination);

        if ($destination === '') {
            throw new InvalidArgumentException(
                'A verification destination is required.'
            );
        }

        $code = $this->generateCode();

        $challenge = DB::transaction(function () use (
            $user,
            $purpose,
            $channel,
            $destination,
            $code
        ): VerificationChallenge {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Only one challenge for a specific account and purpose should
             * remain usable at a time.
             *
             * Older challenges are consumed before the replacement is issued,
             * including previously verified but unfinished challenges.
             */
            VerificationChallenge::query()
                ->where('user_id', $lockedUser->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update([
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

            return VerificationChallenge::create([
                'user_id' => $lockedUser->id,
                'public_id' => (string) Str::ulid(),
                'purpose' => $purpose,
                'channel' => $channel,
                'destination' => $destination,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'resend_count' => 0,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            ]);
        });

        return [
            'challenge' => $challenge,
            'code' => $code,
        ];
    }

    /**
     * Replace the OTP for an existing challenge.
     *
     * Expired challenges may be resent, but verified or consumed challenges
     * cannot be reopened.
     *
     * @return array{
     *     challenge: VerificationChallenge,
     *     code: string
     * }
     */
    public function resend(
        VerificationChallenge $challenge
    ): array {
        $code = $this->generateCode();

        $challenge = DB::transaction(function () use (
            $challenge,
            $code
        ): VerificationChallenge {
            $lockedChallenge = VerificationChallenge::query()
                ->whereKey($challenge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChallenge->isConsumed()) {
                throw new DomainException(
                    'This verification challenge has already been completed.'
                );
            }

            if ($lockedChallenge->isVerified()) {
                throw new DomainException(
                    'This verification challenge has already been verified.'
                );
            }

            if (
                $lockedChallenge->resend_count
                >= self::MAX_RESENDS
            ) {
                throw new DomainException(
                    'The maximum number of verification-code resends has been reached.'
                );
            }

            $nextAllowedSendAt = $lockedChallenge->last_sent_at
                ->copy()
                ->addSeconds(
                    self::RESEND_COOLDOWN_SECONDS
                );

            if ($nextAllowedSendAt->isFuture()) {
                throw new DomainException(
                    'Please wait before requesting another verification code.'
                );
            }

            $lockedChallenge->forceFill([
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'resend_count' =>
                $lockedChallenge->resend_count + 1,
                'last_sent_at' => now(),
                'expires_at' => now()->addMinutes(
                    self::EXPIRY_MINUTES
                ),
            ])->save();

            return $lockedChallenge->fresh();
        });

        return [
            'challenge' => $challenge,
            'code' => $code,
        ];
    }

    /**
     * Verify an OTP against a challenge.
     *
     * This lower-level method verifies only the challenge. Controller-facing
     * workflows that also change account state should use complete() so that
     * verification, the account mutation, and challenge consumption happen
     * atomically.
     *
     * Failed verification attempts are committed before an invalid-code
     * exception is returned to the caller. This prevents transaction rollback
     * from erasing the security counter.
     */
    public function verify(
        VerificationChallenge $challenge,
        string $code
    ): VerificationChallenge {
        $result = DB::transaction(function () use (
            $challenge,
            $code
        ): array {
            $lockedChallenge = VerificationChallenge::query()
                ->whereKey($challenge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChallenge->isConsumed()) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification challenge has already been completed.',
                ];
            }

            if ($lockedChallenge->isVerified()) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification challenge has already been verified.',
                ];
            }

            if (
                $lockedChallenge->expires_at
                ->lessThanOrEqualTo(now())
            ) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification code has expired.',
                ];
            }

            if (
                $lockedChallenge->attempts
                >= self::MAX_ATTEMPTS
            ) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'The maximum number of verification attempts has been reached.',
                ];
            }

            if (
                ! Hash::check(
                    trim($code),
                    $lockedChallenge->code_hash
                )
            ) {
                $lockedChallenge->forceFill([
                    'attempts' =>
                    $lockedChallenge->attempts + 1,
                ])->save();

                return [
                    'challenge' =>
                    $lockedChallenge->fresh(),
                    'error' =>
                    'The verification code is invalid.',
                ];
            }

            $lockedChallenge->forceFill([
                'verified_at' => now(),
            ])->save();

            return [
                'challenge' =>
                $lockedChallenge->fresh(),
                'error' => null,
            ];
        });

        if ($result['error'] !== null) {
            throw new DomainException(
                $result['error']
            );
        }

        return $result['challenge'];
    }

    /**
     * Verify a challenge, perform its protected account action, and consume
     * the challenge in a single database transaction.
     *
     * The callback must contain database/account-state work only. External
     * network operations such as email or SMS delivery must not be performed
     * inside this transaction.
     *
     * Callback signature:
     *
     * function (
     *     User $lockedUser,
     *     VerificationChallenge $lockedChallenge
     * ): void
     */
    public function complete(
        VerificationChallenge $challenge,
        string $code,
        string $expectedPurpose,
        Closure $completion
    ): VerificationChallenge {
        $this->assertSupportedPurpose(
            $expectedPurpose
        );

        $result = DB::transaction(function () use (
            $challenge,
            $code,
            $expectedPurpose,
            $completion
        ): array {
            $lockedChallenge = VerificationChallenge::query()
                ->whereKey($challenge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChallenge->isConsumed()) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification challenge has already been completed.',
                ];
            }

            if ($lockedChallenge->isVerified()) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification challenge has already been verified.',
                ];
            }

            if (
                $lockedChallenge->purpose
                !== $expectedPurpose
            ) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification challenge cannot be used for this action.',
                ];
            }

            if (
                $lockedChallenge->expires_at
                ->lessThanOrEqualTo(now())
            ) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'This verification code has expired.',
                ];
            }

            if (
                $lockedChallenge->attempts
                >= self::MAX_ATTEMPTS
            ) {
                return [
                    'challenge' => $lockedChallenge,
                    'error' =>
                    'The maximum number of verification attempts has been reached.',
                ];
            }

            if (
                ! Hash::check(
                    trim($code),
                    $lockedChallenge->code_hash
                )
            ) {
                $lockedChallenge->forceFill([
                    'attempts' =>
                    $lockedChallenge->attempts + 1,
                ])->save();

                return [
                    'challenge' =>
                    $lockedChallenge->fresh(),
                    'error' =>
                    'The verification code is invalid.',
                ];
            }

            $lockedUser = User::query()
                ->whereKey(
                    $lockedChallenge->user_id
                )
                ->lockForUpdate()
                ->firstOrFail();

            $verifiedAt = now();

            $lockedChallenge->forceFill([
                'verified_at' => $verifiedAt,
            ])->save();

            /*
             * Any exception from the protected account mutation rolls back
             * verified_at together with the account changes. That leaves no
             * reusable half-completed verification state.
             */
            $completion(
                $lockedUser,
                $lockedChallenge
            );

            $lockedChallenge->forceFill([
                'consumed_at' => now(),
            ])->save();

            return [
                'challenge' =>
                $lockedChallenge->fresh(),
                'error' => null,
            ];
        });

        if ($result['error'] !== null) {
            throw new DomainException(
                $result['error']
            );
        }

        return $result['challenge'];
    }

    /**
     * Mark a verified challenge as permanently consumed.
     *
     * Retained for lower-level/internal workflows. User-facing account actions
     * should normally use complete().
     */
    public function consume(
        VerificationChallenge $challenge
    ): VerificationChallenge {
        return DB::transaction(function () use (
            $challenge
        ): VerificationChallenge {
            $lockedChallenge = VerificationChallenge::query()
                ->whereKey($challenge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChallenge->isConsumed()) {
                return $lockedChallenge;
            }

            if (! $lockedChallenge->isVerified()) {
                throw new DomainException(
                    'The verification challenge must be verified before it can be completed.'
                );
            }

            $lockedChallenge->forceFill([
                'consumed_at' => now(),
            ])->save();

            return $lockedChallenge->fresh();
        });
    }

    /**
     * Permanently revoke a challenge that must no longer be usable.
     */
    public function revoke(
        VerificationChallenge $challenge
    ): VerificationChallenge {
        return DB::transaction(function () use (
            $challenge
        ): VerificationChallenge {
            $lockedChallenge = VerificationChallenge::query()
                ->whereKey($challenge->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedChallenge->isConsumed()) {
                return $lockedChallenge;
            }

            $lockedChallenge->forceFill([
                'consumed_at' => now(),
            ])->save();

            return $lockedChallenge->fresh();
        });
    }

    /**
     * Number of whole seconds before another resend is permitted.
     */
    public function resendAvailableIn(
        VerificationChallenge $challenge
    ): int {
        $availableAt = $challenge->last_sent_at
            ->copy()
            ->addSeconds(
                self::RESEND_COOLDOWN_SECONDS
            );

        if ($availableAt->lessThanOrEqualTo(now())) {
            return 0;
        }

        return max(
            0,
            (int) ceil(
                now()->diffInSeconds(
                    $availableAt,
                    false
                )
            )
        );
    }

    private function generateCode(): string
    {
        $maximum =
            (10 ** self::CODE_LENGTH) - 1;

        return str_pad(
            (string) random_int(
                0,
                $maximum
            ),
            self::CODE_LENGTH,
            '0',
            STR_PAD_LEFT
        );
    }

    private function assertSupportedPurpose(
        string $purpose
    ): void {
        $supportedPurposes = [
            VerificationChallenge::PURPOSE_REGISTRATION,
            VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            VerificationChallenge::PURPOSE_PASSWORD_RESET,
        ];

        if (
            ! in_array(
                $purpose,
                $supportedPurposes,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported verification purpose.'
            );
        }
    }

    private function assertSupportedChannel(
        string $channel
    ): void {
        $supportedChannels = [
            VerificationChallenge::CHANNEL_EMAIL,
            VerificationChallenge::CHANNEL_SMS,
        ];

        if (
            ! in_array(
                $channel,
                $supportedChannels,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported verification channel.'
            );
        }
    }
}
