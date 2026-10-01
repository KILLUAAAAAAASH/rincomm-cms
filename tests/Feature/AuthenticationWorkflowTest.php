<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_log_in_and_is_redirected_to_admin_dashboard(): void
    {
        $user = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_staff_can_log_in_and_is_redirected_to_admin_dashboard(): void
    {
        $user = $this->createUser(
            role: 'staff',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_technician_can_log_in_and_is_redirected_to_technician_dashboard(): void
    {
        $user = $this->createUser(
            role: 'technician',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('technician.dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_active_customer_can_log_in_and_is_redirected_to_customer_dashboard(): void
    {
        $user = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('customer.dashboard')
        );

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_user(): void
    {
        $user = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]
        );

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_remain_authenticated(): void
    {
        $user = $this->createUser(
            role: 'customer',
            status: 'inactive'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unknown_role_is_logged_out_safely(): void
    {
        $user = $this->createUser(
            role: 'unknown-role',
            status: 'active'
        );

        $response = $this->post(
            route('login.store'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(
            route('login')
        );

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(
            route('home')
        );

        $this->assertGuest();
    }

    private function createUser(
        string $role,
        string $status
    ): User {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $user->forceFill([
            'role' => $role,
            'account_status' => $status,
        ])->save();

        return $user->refresh();
    }
}
