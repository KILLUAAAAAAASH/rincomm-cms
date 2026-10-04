<?php

namespace Tests\Unit;

use App\Models\VerificationChallenge;
use App\Services\IprogSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class IprogSmsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.iprog_sms.base_url' =>
                'https://www.iprog.test',
            'services.iprog_sms.api_token' =>
                'test-api-token',
            'services.iprog_sms.timeout' =>
                10,
        ]);
    }

    public function test_it_sends_registration_otp_to_iprog(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response([
                    'status' => 200,
                    'message' => 'Message queued.',
                    'message_id' => 'MSG-1001',
                ], 200),
        ]);

        $service = app(IprogSmsService::class);

        $result = $service->sendVerificationCode(
            phoneNumber: '639171234567',
            code: '123456',
            purpose:
                VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );

        $this->assertSame(
            'MSG-1001',
            $result['message_id']
        );

        $this->assertSame(
            '639171234567',
            $result['recipient']
        );

        Http::assertSentCount(1);

        Http::assertSent(
            function (Request $request): bool {
                return $request->url()
                        === 'https://www.iprog.test/api/v1/sms_messages'
                    && $request->method() === 'POST'
                    && $request['api_token']
                        === 'test-api-token'
                    && $request['phone_number']
                        === '639171234567'
                    && $request['message']
                        === 'Rincomm registration code: 123456. Expires in 10 minutes. Do not share this code.';
            }
        );
    }

    public function test_it_uses_employee_activation_message(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response([
                    'status' => 200,
                    'message' => 'Message queued.',
                    'message_id' => 'MSG-1002',
                ], 200),
        ]);

        $service = app(IprogSmsService::class);

        $service->sendVerificationCode(
            phoneNumber: '639181234567',
            code: '654321',
            purpose:
                VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            expiresInMinutes: 10
        );

        Http::assertSent(
            fn (Request $request): bool =>
                $request['message']
                === 'Rincomm employee activation code: 654321. Expires in 10 minutes. Do not share this code.'
        );
    }

    public function test_it_uses_password_reset_message(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response([
                    'status' => 200,
                    'message' => 'Message queued.',
                    'message_id' => 'MSG-1003',
                ], 200),
        ]);

        $service = app(IprogSmsService::class);

        $service->sendVerificationCode(
            phoneNumber: '639191234567',
            code: '246810',
            purpose:
                VerificationChallenge::PURPOSE_PASSWORD_RESET,
            expiresInMinutes: 10
        );

        Http::assertSent(
            fn (Request $request): bool =>
                $request['message']
                === 'Rincomm password reset code: 246810. Expires in 10 minutes. Do not share this code.'
        );
    }

    public function test_missing_api_token_prevents_request(): void
    {
        config([
            'services.iprog_sms.api_token' => '',
        ]);

        Http::fake();

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'IPROG SMS API token is not configured.'
        );

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '123456',
                purpose:
                    VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_invalid_phone_prevents_request(): void
    {
        Http::fake();

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A valid Philippine mobile number is required for SMS verification.'
        );

        try {
            $service->sendVerificationCode(
                phoneNumber: '09171234567',
                code: '123456',
                purpose:
                    VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_invalid_code_prevents_request(): void
    {
        Http::fake();

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The verification code must contain exactly six digits.'
        );

        try {
            $service->sendVerificationCode(
                phoneNumber: '639171234567',
                code: '12345A',
                purpose:
                    VerificationChallenge::PURPOSE_REGISTRATION,
                expiresInMinutes: 10
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_http_failure_is_reported(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response([
                    'message' => 'Unauthorized',
                ], 401),
        ]);

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'IPROG SMS verification delivery failed with HTTP status 401.'
        );

        $service->sendVerificationCode(
            phoneNumber: '639171234567',
            code: '123456',
            purpose:
                VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );
    }

    public function test_rejected_provider_status_is_reported(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response([
                    'status' => 400,
                    'message' => 'Rejected',
                    'message_id' => null,
                ], 200),
        ]);

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'IPROG SMS did not accept the verification message for delivery.'
        );

        $service->sendVerificationCode(
            phoneNumber: '639171234567',
            code: '123456',
            purpose:
                VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );
    }

    public function test_invalid_provider_response_is_rejected(): void
    {
        Http::fake([
            'https://www.iprog.test/api/v1/sms_messages' =>
                Http::response('invalid-response', 200),
        ]);

        $service = app(IprogSmsService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'IPROG SMS returned an invalid delivery response.'
        );

        $service->sendVerificationCode(
            phoneNumber: '639171234567',
            code: '123456',
            purpose:
                VerificationChallenge::PURPOSE_REGISTRATION,
            expiresInMinutes: 10
        );
    }
}
