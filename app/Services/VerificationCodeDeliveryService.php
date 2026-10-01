<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\VerificationChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class VerificationCodeDeliveryService
{
    public function __construct(
        private readonly SemaphoreSmsService $semaphoreSmsService,
        private readonly VerificationChallengeService $challengeService
    ) {}

    public function deliver(
        VerificationChallenge $challenge,
        string $code
    ): void {
        $challenge = $challenge->fresh();

        if (! $challenge instanceof VerificationChallenge) {
            throw new RuntimeException(
                'The verification challenge no longer exists.'
            );
        }

        if (! $challenge->isPending()) {
            throw new RuntimeException(
                'Only pending verification challenges can be delivered.'
            );
        }

        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            $this->revokeSafely($challenge);

            throw new RuntimeException(
                'The verification code must contain exactly six digits.'
            );
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $this->revokeSafely($challenge);

            throw new RuntimeException(
                'The verification code does not match the challenge.'
            );
        }

        $destination = trim($challenge->destination);

        if ($destination === '') {
            $this->revokeSafely($challenge);

            throw new RuntimeException(
                'The verification destination is missing.'
            );
        }

        $expiresInMinutes = $this->remainingExpiryMinutes(
            $challenge
        );

        try {
            match ($challenge->channel) {
                VerificationChallenge::CHANNEL_EMAIL =>
                $this->deliverByEmail(
                    $challenge,
                    $code,
                    $expiresInMinutes
                ),

                VerificationChallenge::CHANNEL_SMS =>
                $this->deliverBySms(
                    $challenge,
                    $code,
                    $expiresInMinutes
                ),

                default =>
                throw new RuntimeException(
                    'Unsupported verification delivery channel.'
                ),
            };
        } catch (Throwable $exception) {
            $this->revokeSafely($challenge);

            throw $exception;
        }
    }

    private function deliverByEmail(
        VerificationChallenge $challenge,
        string $code,
        int $expiresInMinutes
    ): void {
        if (
            filter_var(
                $challenge->destination,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'The verification email address is invalid.'
            );
        }

        Mail::to($challenge->destination)->send(
            new VerificationCodeMail(
                code: $code,
                purpose: $challenge->purpose,
                expiresInMinutes: $expiresInMinutes
            )
        );
    }

    private function deliverBySms(
        VerificationChallenge $challenge,
        string $code,
        int $expiresInMinutes
    ): void {
        $this->semaphoreSmsService->sendVerificationCode(
            phoneNumber: $challenge->destination,
            code: $code,
            purpose: $challenge->purpose,
            expiresInMinutes: $expiresInMinutes
        );
    }

    private function remainingExpiryMinutes(
        VerificationChallenge $challenge
    ): int {
        $remainingSeconds = now()->diffInSeconds(
            $challenge->expires_at,
            false
        );

        if ($remainingSeconds <= 0) {
            throw new RuntimeException(
                'The verification challenge has expired.'
            );
        }

        return max(
            1,
            (int) ceil($remainingSeconds / 60)
        );
    }

    private function revokeSafely(
        VerificationChallenge $challenge
    ): void {
        try {
            $this->challengeService->revoke($challenge);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
