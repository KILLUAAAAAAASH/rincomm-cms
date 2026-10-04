<?php

namespace App\Services;

use App\Models\VerificationChallenge;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IprogSmsService
{
    public function sendVerificationCode(
        string $phoneNumber,
        string $code,
        string $purpose,
        int $expiresInMinutes
    ): array {
        $phoneNumber = trim($phoneNumber);
        $code = trim($code);

        if (! preg_match('/^639\d{9}$/', $phoneNumber)) {
            throw new RuntimeException(
                'A valid Philippine mobile number is required for SMS verification.'
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
            trim((string) config('services.iprog_sms.base_url')),
            '/'
        );

        $apiToken = trim(
            (string) config('services.iprog_sms.api_token')
        );

        $timeout = max(
            1,
            (int) config('services.iprog_sms.timeout', 10)
        );

        if ($baseUrl === '') {
            throw new RuntimeException(
                'IPROG SMS base URL is not configured.'
            );
        }

        if ($apiToken === '') {
            throw new RuntimeException(
                'IPROG SMS API token is not configured.'
            );
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->post(
                "{$baseUrl}/api/v1/sms_messages",
                [
                    'api_token' => $apiToken,
                    'phone_number' => $phoneNumber,
                    'message' => $this->messageForPurpose(
                        $code,
                        $purpose,
                        $expiresInMinutes
                    ),
                ]
            );

        $this->ensureSuccessfulResponse($response);

        $result = $response->json();

        if (! is_array($result)) {
            throw new RuntimeException(
                'IPROG SMS returned an invalid delivery response.'
            );
        }

        $providerStatus = (int) ($result['status'] ?? 0);

        if (
            $providerStatus !== 200
            || empty($result['message_id'])
        ) {
            throw new RuntimeException(
                'IPROG SMS did not accept the verification message for delivery.'
            );
        }

        return [
            'message_id' => $result['message_id'],
            'recipient' => $phoneNumber,
            'status' => $result['message'] ?? 'Queued',
        ];
    }

    private function ensureSuccessfulResponse(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            'IPROG SMS verification delivery failed with HTTP status '
                . $response->status()
                . '.'
        );
    }

    private function messageForPurpose(
        string $code,
        string $purpose,
        int $expiresInMinutes
    ): string {
        $action = match ($purpose) {
            VerificationChallenge::PURPOSE_REGISTRATION =>
                'registration',

            VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION =>
                'employee activation',

            VerificationChallenge::PURPOSE_PASSWORD_RESET =>
                'password reset',

            default =>
                'account verification',
        };

        return sprintf(
            'Rincomm %s code: %s. Expires in %d minutes. Do not share this code.',
            $action,
            $code,
            $expiresInMinutes
        );
    }
}
