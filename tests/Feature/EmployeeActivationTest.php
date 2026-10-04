<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationChallenge;
use App\Services\VerificationChallengeService;
use App\Services\VerificationCodeDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class EmployeeActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_channel_chooser_requires_the_credential_verified_session(): void
    {
        $response = $this->get(
            route('employee.activation.channel')
        );

        $response->assertNotFound();

        $this->assertGuest();
    }

    public function test_pending_employee_can_open_verification_method_chooser(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0001',
            phone: '639171234567'
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,
            ])
            ->get(
                route(
                    'employee.activation.channel'
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Email'
        );

        $response->assertSee(
            'SMS'
        );

        $this->assertGuest();
    }

    public function test_pending_employee_can_choose_email_for_activation(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0002',
            phone: '639181234567'
        );

        $this->mockDeliveryForChannel(
            user: $user,
            channel:
                VerificationChallenge::CHANNEL_EMAIL
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,
            ])
            ->post(
                route(
                    'employee.activation.channel.store'
                ),
                [
                    'verification_channel' =>
                        VerificationChallenge::CHANNEL_EMAIL,
                ]
            );

        $challenge =
            VerificationChallenge::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'purpose',
                    VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            VerificationChallenge::CHANNEL_EMAIL,
            $challenge->channel
        );

        $this->assertSame(
            mb_strtolower($user->email),
            mb_strtolower(
                $challenge->destination
            )
        );

        $response->assertRedirect(
            route(
                'employee.activation',
                $challenge
            )
        );

        $response->assertSessionHas(
            'employee.activation.challenge',
            $challenge->public_id
        );

        $this->assertGuest();
    }

    public function test_pending_employee_can_choose_sms_for_activation(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0003',
            phone: '639191234567'
        );

        $this->mockDeliveryForChannel(
            user: $user,
            channel:
                VerificationChallenge::CHANNEL_SMS
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,
            ])
            ->post(
                route(
                    'employee.activation.channel.store'
                ),
                [
                    'verification_channel' =>
                        VerificationChallenge::CHANNEL_SMS,
                ]
            );

        $challenge =
            VerificationChallenge::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'purpose',
                    VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION
                )
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            VerificationChallenge::CHANNEL_SMS,
            $challenge->channel
        );

        $this->assertSame(
            $user->phone,
            $challenge->destination
        );

        $response->assertRedirect(
            route(
                'employee.activation',
                $challenge
            )
        );

        $response->assertSessionHas(
            'employee.activation.challenge',
            $challenge->public_id
        );

        $this->assertGuest();
    }

    public function test_switching_verification_channel_invalidates_previous_activation_challenge(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0004',
            phone: '639201234567'
        );

        $this->mock(
            VerificationCodeDeliveryService::class,
            function (
                MockInterface $mock
            ): void {
                $mock
                    ->shouldReceive('deliver')
                    ->twice();
            }
        );

        $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,
            ])
            ->post(
                route(
                    'employee.activation.channel.store'
                ),
                [
                    'verification_channel' =>
                        VerificationChallenge::CHANNEL_EMAIL,
                ]
            )
            ->assertRedirect();

        $emailChallenge =
            VerificationChallenge::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->latest('id')
                ->firstOrFail();

        $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,
            ])
            ->post(
                route(
                    'employee.activation.channel.store'
                ),
                [
                    'verification_channel' =>
                        VerificationChallenge::CHANNEL_SMS,
                ]
            )
            ->assertRedirect();

        $smsChallenge =
            VerificationChallenge::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->latest('id')
                ->firstOrFail();

        $emailChallenge->refresh();

        $this->assertNotSame(
            $emailChallenge->public_id,
            $smsChallenge->public_id
        );

        $this->assertNotNull(
            $emailChallenge->consumed_at
        );

        $this->assertSame(
            VerificationChallenge::CHANNEL_SMS,
            $smsChallenge->channel
        );

        $this->assertNull(
            $smsChallenge->consumed_at
        );
    }

    public function test_staff_can_complete_sms_activation_and_set_permanent_password(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0005',
            phone: '639211234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_SMS
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,

                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'employee.activation.store',
                    $issued['challenge']
                ),
                [
                    'code' =>
                        $issued['code'],

                    'password' =>
                        'NewPassword123!',

                    'password_confirmation' =>
                        'NewPassword123!',
                ]
            );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $user->refresh();
        $issued['challenge']->refresh();

        $this->assertSame(
            'active',
            $user->account_status
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertNull(
            $user->email_verified_at
        );

        $this->assertNotNull(
            $user->activation_completed_at
        );

        $this->assertTrue(
            Hash::check(
                'NewPassword123!',
                $user->password
            )
        );

        $this->assertFalse(
            Hash::check(
                '12345678',
                $user->password
            )
        );

        $this->assertNotNull(
            $issued['challenge']->verified_at
        );

        $this->assertNotNull(
            $issued['challenge']->consumed_at
        );

        $this->assertNull(
            session(
                'employee.activation.user_id'
            )
        );

        $this->assertNull(
            session(
                'employee.activation.challenge'
            )
        );
    }

    public function test_staff_can_complete_email_activation_and_set_permanent_password(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0006',
            phone: '639221234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_EMAIL
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,

                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'employee.activation.store',
                    $issued['challenge']
                ),
                [
                    'code' =>
                        $issued['code'],

                    'password' =>
                        'EmailPassword123!',

                    'password_confirmation' =>
                        'EmailPassword123!',
                ]
            );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $user->refresh();

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

        $this->assertTrue(
            Hash::check(
                'EmailPassword123!',
                $user->password
            )
        );
    }

    public function test_technician_activation_redirects_to_technician_dashboard(): void
    {
        $user = $this->createPendingEmployee(
            role: 'technician',
            employeeNumber: 'EMP-0007',
            phone: '639231234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_SMS
        );

        $response = $this
            ->withSession([
                'employee.activation.user_id' =>
                    $user->id,

                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'employee.activation.store',
                    $issued['challenge']
                ),
                [
                    'code' =>
                        $issued['code'],

                    'password' =>
                        'Technician123!',

                    'password_confirmation' =>
                        'Technician123!',
                ]
            );

        $response->assertRedirect(
            route(
                'technician.dashboard'
            )
        );

        $this->assertAuthenticatedAs(
            $user
        );

        $user->refresh();

        $this->assertSame(
            'active',
            $user->account_status
        );

        $this->assertNotNull(
            $user->phone_verified_at
        );

        $this->assertNotNull(
            $user->activation_completed_at
        );
    }

    public function test_default_password_cannot_be_reused_during_activation(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0008',
            phone: '639241234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_SMS
        );

        $response = $this
            ->withSession([
                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'employee.activation.store',
                    $issued['challenge']
                ),
                [
                    'code' =>
                        $issued['code'],

                    'password' =>
                        '12345678',

                    'password_confirmation' =>
                        '12345678',
                ]
            );

        $response->assertSessionHasErrors(
            'password'
        );

        $this->assertGuest();

        $user->refresh();
        $issued['challenge']->refresh();

        $this->assertSame(
            'pending_verification',
            $user->account_status
        );

        $this->assertNull(
            $user->phone_verified_at
        );

        $this->assertNull(
            $user->activation_completed_at
        );

        $this->assertTrue(
            Hash::check(
                '12345678',
                $user->password
            )
        );

        $this->assertNull(
            $issued['challenge']->verified_at
        );

        $this->assertNull(
            $issued['challenge']->consumed_at
        );
    }

    public function test_invalid_employee_activation_otp_keeps_account_pending(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0009',
            phone: '639251234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_SMS
        );

        $wrongCode =
            $issued['code'] === '000000'
                ? '999999'
                : '000000';

        $response = $this
            ->withSession([
                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->post(
                route(
                    'employee.activation.store',
                    $issued['challenge']
                ),
                [
                    'code' =>
                        $wrongCode,

                    'password' =>
                        'AnotherPassword123!',

                    'password_confirmation' =>
                        'AnotherPassword123!',
                ]
            );

        $response->assertSessionHasErrors(
            'code'
        );

        $this->assertGuest();

        $user->refresh();
        $issued['challenge']->refresh();

        $this->assertSame(
            'pending_verification',
            $user->account_status
        );

        $this->assertNull(
            $user->activation_completed_at
        );

        $this->assertSame(
            1,
            $issued['challenge']->attempts
        );

        $this->assertNull(
            $issued['challenge']->verified_at
        );

        $this->assertNull(
            $issued['challenge']->consumed_at
        );
    }

    public function test_employee_activation_challenge_is_bound_to_the_starting_session(): void
    {
        $user = $this->createPendingEmployee(
            role: 'staff',
            employeeNumber: 'EMP-0010',
            phone: '639261234567'
        );

        $issued = $this->issueActivationChallenge(
            $user,
            VerificationChallenge::CHANNEL_SMS
        );

        $this
            ->withSession([
                'employee.activation.challenge' =>
                    $issued['challenge']->public_id,
            ])
            ->get(
                route(
                    'employee.activation',
                    $issued['challenge']
                )
            )
            ->assertOk();

        $this->flushSession();

        $response = $this->get(
            route(
                'employee.activation',
                $issued['challenge']
            )
        );

        $response->assertNotFound();

        $this->assertGuest();

        $this->assertSame(
            'pending_verification',
            $user->fresh()->account_status
        );
    }

    private function mockDeliveryForChannel(
        User $user,
        string $channel
    ): void {
        $this->mock(
            VerificationCodeDeliveryService::class,
            function (
                MockInterface $mock
            ) use (
                $user,
                $channel
            ): void {
                $mock
                    ->shouldReceive('deliver')
                    ->once()
                    ->withArgs(
                        function (
                            VerificationChallenge $challenge,
                            string $code
                        ) use (
                            $user,
                            $channel
                        ): bool {
                            $expectedDestination =
                                $channel
                                === VerificationChallenge::CHANNEL_EMAIL
                                    ? mb_strtolower(
                                        $user->email
                                    )
                                    : $user->phone;

                            return
                                $challenge->user_id
                                    === $user->id
                                && $challenge->purpose
                                    === VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION
                                && $challenge->channel
                                    === $channel
                                && mb_strtolower(
                                    $challenge->destination
                                )
                                    === mb_strtolower(
                                        $expectedDestination
                                    )
                                && preg_match(
                                    '/^\d{6}$/',
                                    $code
                                ) === 1;
                        }
                    );
            }
        );
    }

    /**
     * @return array{
     *     challenge: VerificationChallenge,
     *     code: string
     * }
     */
    private function issueActivationChallenge(
        User $user,
        string $channel
    ): array {
        $destination =
            $channel
            === VerificationChallenge::CHANNEL_EMAIL
                ? $user->email
                : $user->phone;

        return app(
            VerificationChallengeService::class
        )->issue(
            user: $user,
            purpose:
                VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION,
            channel:
                $channel,
            destination:
                $destination
        );
    }

    private function createPendingEmployee(
        string $role,
        string $employeeNumber,
        string $phone
    ): User {
        $user = User::factory()->create([
            'name' =>
                'Test Employee',

            'email' =>
                strtolower(
                    $employeeNumber
                )
                . '@example.test',

            'phone' =>
                $phone,

            'password' =>
                '12345678',
        ]);

        $user->forceFill([
            'employee_number' =>
                $employeeNumber,

            'role' =>
                $role,

            'account_status' =>
                'pending_verification',

            'email_verified_at' =>
                null,

            'phone_verified_at' =>
                null,

            'activation_completed_at' =>
                null,
        ])->save();

        return $user->fresh();
    }
}
