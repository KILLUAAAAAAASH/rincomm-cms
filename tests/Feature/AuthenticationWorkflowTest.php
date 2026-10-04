<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_log_in_with_email_and_is_redirected_to_dashboard(): void
    {
        $user = $this->createAccountUser(
            role: 'admin',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'account',
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_customer_can_log_in_with_email_and_is_redirected_to_customer_dashboard(): void
    {
        $user = $this->createAccountUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'account',
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('customer.dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_staff_can_log_in_with_employee_number(): void
    {
        $user = $this->createEmployeeUser(
            role: 'staff',
            employeeNumber: 'EMP-0001',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'employee',
                'employee_number' => 'emp-0001',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_technician_can_log_in_with_employee_number(): void
    {
        $user = $this->createEmployeeUser(
            role: 'technician',
            employeeNumber: 'EMP-0002',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'employee',
                'employee_number' => 'EMP-0002',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('technician.dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_cannot_use_standard_email_login(): void
    {
        $user = $this->createEmployeeUser(
            role: 'staff',
            employeeNumber: 'EMP-0003',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'account',
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertSessionHasErrors(
            'email'
        );

        $this->assertGuest();
    }

    public function test_admin_cannot_use_employee_login(): void
    {
        $user = $this->createAccountUser(
            role: 'admin',
            status: 'active'
        );

        $user->forceFill([
            'employee_number' => 'EMP-9001',
        ])->save();

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'employee',
                'employee_number' => 'EMP-9001',
                'password' => 'password',
            ]
        );

        $response->assertSessionHasErrors(
            'employee_number'
        );

        $this->assertGuest();
    }

    public function test_pending_employee_with_default_password_is_redirected_to_verification_method_chooser_without_authenticating(): void
    {
        $user = $this->createEmployeeUser(
            role: 'staff',
            employeeNumber: 'EMP-0004',
            status: 'pending_verification',
            password: '12345678',
            activated: false
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'employee',
                'employee_number' => 'EMP-0004',
                'password' => '12345678',
            ]
        );

        $response->assertRedirect(
            route('employee.activation.channel')
        );

        $response->assertSessionHas(
            'employee.activation.user_id',
            $user->id
        );

        $response->assertSessionMissing(
            'employee.activation.challenge'
        );

        $this->assertSame(
            0,
            VerificationChallenge::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'purpose',
                    VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION
                )
                ->count()
        );

        $this->assertGuest();

        $user->refresh();

        $this->assertSame(
            'pending_verification',
            $user->account_status
        );

        $this->assertNull(
            $user->activation_completed_at
        );
    }

    public function test_inactive_employee_cannot_log_in(): void
    {
        $user = $this->createEmployeeUser(
            role: 'staff',
            employeeNumber: 'EMP-0005',
            status: 'inactive'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'employee',
                'employee_number' => 'EMP-0005',
                'password' => 'password',
            ]
        );

        $response->assertSessionHasErrors(
            'employee_number'
        );

        $this->assertGuest();
    }

    public function test_invalid_standard_login_credentials_do_not_authenticate_user(): void
    {
        $user = $this->createAccountUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'account',
                'email' => $user->email,
                'password' => 'wrong-password',
            ]
        );

        $response->assertSessionHasErrors(
            'email'
        );

        $this->assertGuest();
    }

    public function test_inactive_customer_cannot_log_in(): void
    {
        $user = $this->createAccountUser(
            role: 'customer',
            status: 'inactive'
        );

        $response = $this->post(
            route('login.store'),
            [
                'login_type' => 'account',
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertSessionHasErrors(
            'email'
        );

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = $this->createAccountUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('logout')
            );

        $response->assertRedirect(
            route('home')
        );

        $this->assertGuest();
    }

    private function createAccountUser(
        string $role,
        string $status
    ): User {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $user->forceFill([
            'role' => $role,
            'account_status' => $status,
        ])->save();

        return $user->fresh();
    }

    private function createEmployeeUser(
        string $role,
        string $employeeNumber,
        string $status,
        string $password = 'password',
        bool $activated = true
    ): User {
        $user = User::factory()->create([
            'phone' => '63917'
                . str_pad(
                    (string) random_int(
                        1000000,
                        9999999
                    ),
                    7,
                    '0',
                    STR_PAD_LEFT
                ),
            'password' => $password,
        ]);

        $user->forceFill([
            'employee_number' =>
                $employeeNumber,

            'role' =>
                $role,

            'account_status' =>
                $status,

            'email_verified_at' =>
                $activated
                    ? now()
                    : null,

            'phone_verified_at' =>
                $activated
                    ? now()
                    : null,

            'activation_completed_at' =>
                $activated
                    ? now()
                    : null,
        ])->save();

        return $user->fresh();
    }
}
