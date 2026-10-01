<?php

namespace Tests\Unit;

use App\Models\VerificationChallenge;
use App\Services\SemaphoreSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SemaphoreSmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.semaphore.base_url' => 'https://api.semaphore.test',
            'services.semaphore.api_key' => 'test-api-key',
            'services.semaphore.sender_name' => 'RINCOMM',
            'services.semaphore.timeout' => 10,
        ]);
    }

    public function test_it_sends_registration_otp_to_the_semaphore_otp_endpoint(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response([
                [
                    'message_id' => 1001,
                    'recipient' => '639171234567',
                    'status' => 'Pending',
                ],
            ], 200),
        ]);

        $service = app(SemaphoreSmsService::class);

        $result = $service->sendVerificationCode(
            phoneNumber: '639171234567',
            code: '123456',
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );

        $this->assertSame(1001, $result['message_id']);
        $this->assertSame('639171234567', $result['recipient']);
        $this->assertSame('Pending', $result['status']);

        Http::assertSentCount(1);

        Http::assertSent(function (Request $request): bool {
            return $request->url()
                === 'https://api.semaphore.test/api/v4/otp'
                && $request->method() === 'POST'
                && $request['apikey'] === 'test-api-key'
                && $request['number'] === '639171234567'
                && $request['code'] === '123456'
                && $request['sendername'] === 'RINCOMM'
                && $request['message']
                === 'Rincomm registration code: {otp}. Expires in 10 minutes. Do not share this code.';
        });
    }

    public function test_it_uses_the_correct_message_for_employee_activation(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response([
                [
                    'message_id' => 1002,
                    'recipient' => '639181234567',
                    'status' => 'Pending',
                ],
            ], 200),
        ]);

        $service = app(SemaphoreSmsService::class);

        $service->sendVerificationCode(
            phoneNumber: '639181234567',
            code: '654321',
            purpose: VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            expiresInMinutes: 10
        );

        Http::assertSent(function (Request $request): bool {
            return $request['message']
                === 'Rincomm employee account activation code: {otp}. Expires in 10 minutes. Do not share this code.';
        });
    }

    public function test_it_uses_the_correct_message_for_password_reset(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response([
                [
                    'message_id' => 1003,
                    'recipient' => '639191234567',
                    'status' => 'Pending',
                ],
            ], 200),
        ]);

        $service = app(SemaphoreSmsService::class);

        $service->sendVerificationCode(
            phoneNumber: '639191234567',
            code: '246810',
            purpose: VerificationChallenge::PURPOSE_PASSWORD_RESET,
            expiresInMinutes: 10
        );

        Http::assertSent(function (Request $request): bool {
            return $request['message']
                === 'Rincomm password reset code: {otp}. Expires in 10 minutes. Do not share this code.';
        });
    }

    public function test_sender_name_is_omitted_when_not_configured(): void
    {
        config([
            'services.semaphore.sender_name' => '',
        ]);

        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response([
                [
                    'message_id' => 1004,
                    'recipient' => '639171111111',
                    'status' => 'Pending',
                ],
            ], 200),
        ]);

        $service = app(SemaphoreSmsService::class);

        $service->sendVerificationCode(
            phoneNumber: '639171111111',
            code: '112233',
            purpose: VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );

        Http::assertSent(function (Request $request): bool {
            return ! isset($request['sendername']);
        });
    }

    public function test_missing_api_key_prevents_any_http_request(): void
    {
        config([
            'services.semaphore.api_key' => '',
        ]);

        Http::fake();

        $service = app(SemaphoreSmsService::class);

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '123456',
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );

            $this->fail(
                'Missing Semaphore credentials should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Semaphore API key is not configured.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_invalid_verification_code_prevents_any_http_request(): void
    {
        Http::fake();

        $service = app(SemaphoreSmsService::class);

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '12345A',
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );

            $this->fail(
                'A malformed verification code should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'The verification code must contain exactly six digits.',
                $exception->getMessage()
            );
        }

        Http::assertNothingSent();
    }

    public function test_http_failure_is_reported_as_delivery_failure(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response(
                ['message' => 'Unauthorized'],
                401
            ),
        ]);

        $service = app(SemaphoreSmsService::class);

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '123456',
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );

            $this->fail(
                'A Semaphore HTTP failure should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Semaphore verification SMS delivery failed with HTTP status 401.',
                $exception->getMessage()
            );
        }

        Http::assertSentCount(1);
    }

    public function test_failed_provider_status_is_rejected(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response([
                [
                    'message_id' => 1005,
                    'recipient' => '639171234567',
                    'status' => 'Failed',
                ],
            ], 200),
        ]);

        $service = app(SemaphoreSmsService::class);

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '123456',
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );

            $this->fail(
                'A failed Semaphore delivery status should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Semaphore did not accept the verification SMS for delivery.',
                $exception->getMessage()
            );
        }
    }

    public function test_invalid_provider_response_is_rejected(): void
    {
        Http::fake([
            'https://api.semaphore.test/api/v4/otp' => Http::response(
                [],
                200
            ),
        ]);

        $service = app(SemaphoreSmsService::class);

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '123456',
                purpose: VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );

            $this->fail(
                'An invalid Semaphore response should have thrown a RuntimeException.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Semaphore returned an invalid SMS delivery response.',
                $exception->getMessage()
            );
        }
    }
}
