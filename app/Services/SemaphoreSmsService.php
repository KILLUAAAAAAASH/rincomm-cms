<?php

namespace App\Services;

use App\Models\VerificationChallenge;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SemaphoreSmsService
{
    public function sendVerificationCode(
        string $phoneNumber,
        string $code,
        string $purpose,
        int $expiresInMinutes
    ): array {
        $phoneNumber = trim($phoneNumber);
        $code = trim($code);

        if ($phoneNumber === '') {
            throw new RuntimeException(
                'A mobile number is required for SMS verification.'
            );
        }

        if (! preg_match('/^\d{6}$/', $code)) {
            throw new RuntimeException(
                'The verification code must contain exactly six digits.'
            );
        }

        if ($expiresInMinutes <= 0) {
            throw new RuntimeException(
                'The verification-code expiry must be greater than zero.'
            );
        }

        $baseUrl = rtrim(
            trim((string) config('services.semaphore.base_url')),
            '/'
        );

        $apiKey = trim(
            (string) config('services.semaphore.api_key')
        );

        $senderName = trim(
            (string) config('services.semaphore.sender_name')
        );

        $timeout = max(
            1,
            (int) config('services.semaphore.timeout', 10)
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'Semaphore base URL is not configured.'
            );
        }

        if ($apiKey === '') {
            throw new RuntimeException(
                'Semaphore API key is not configured.'
            );
        }

        $payload = [
            'apikey' => $apiKey,
            'number' => $phoneNumber,
            'message' => $this->messageForPurpose(
                $purpose,
                $expiresInMinutes
            ),
            'code' => $code,
        ];

        if ($senderName !== '') {
            $payload['sendername'] = $senderName;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($timeout)
            ->post(
                "{$baseUrl}/api/v4/otp",
                $payload
            );

        $this->ensureSuccessfulResponse($response);

        $messages = $response->json();

        if (
            ! is_array($messages)
            || $messages === []
        ) {
            throw new RuntimeException(
                'Semaphore returned an invalid SMS delivery response.'
            );
        }

        $message = $messages[0] ?? null;

        if (! is_array($message)) {
            throw new RuntimeException(
                'Semaphore returned an invalid SMS delivery response.'
            );
        }

        $status = strtolower(
            trim((string) ($message['status'] ?? ''))
        );

        if (
            $status === ''
            || in_array(
                $status,
                ['failed', 'refunded'],
                true
            )
        ) {
            throw new RuntimeException(
                'Semaphore did not accept the verification SMS for delivery.'
            );
        }

        return [
            'message_id' => $message['message_id'] ?? null,
            'recipient' => $message['recipient'] ?? $phoneNumber,
            'status' => $message['status'] ?? null,
        ];
    }

    private function ensureSuccessfulResponse(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            'Semaphore verification SMS delivery failed with HTTP status '
                . $response->status()
                . '.'
        );
    }

    private function messageForPurpose(
        string $purpose,
        int $expiresInMinutes
    ): string {
        $action = match ($purpose) {
            VerificationChallenge::PURPOSE_REGISTRATION =>
            'registration',

            VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION =>
            'employee account activation',

            VerificationChallenge::PURPOSE_PASSWORD_RESET =>
            'password reset',

            default =>
            'account verification',
        };

        return sprintf(
            'Rincomm %s code: {otp}. Expires in %d minutes. Do not share this code.',
            $action,
            $expiresInMinutes
        );
    }
}
