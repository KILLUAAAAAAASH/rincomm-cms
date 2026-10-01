<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountActivationDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deactivate_another_active_user_with_reason(): void
    {
        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $target = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status', $target),
                [
                    'account_status' => 'inactive',
                    'deactivation_reason' => 'Account temporarily disabled.',
                ]
            );

        $response->assertRedirect(
            route('admin.users.index')
        );

        $response->assertSessionHas(
            'success',
            'User account deactivated successfully.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'account_status' => 'inactive',
        ]);
    }

    public function test_admin_can_reactivate_an_inactive_user(): void
    {
        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $target = $this->createUser(
            role: 'customer',
            status: 'inactive'
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status', $target),
                [
                    'account_status' => 'active',
                ]
            );

        $response->assertRedirect(
            route('admin.users.index')
        );

        $response->assertSessionHas(
            'success',
            'User account activated successfully.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'account_status' => 'active',
        ]);
    }

    public function test_deactivation_requires_a_reason(): void
    {
        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $target = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status', $target),
                [
                    'account_status' => 'inactive',
                    'deactivation_reason' => '',
                ]
            );

        $response->assertSessionHasErrors(
            'deactivation_reason'
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'account_status' => 'active',
        ]);
    }

    public function test_user_cannot_deactivate_own_account(): void
    {
        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status', $admin),
                [
                    'account_status' => 'inactive',
                    'deactivation_reason' => 'Self deactivation attempt.',
                ]
            );

        $response->assertRedirect(
            route('admin.users.index')
        );

        $response->assertSessionHas(
            'error',
            'You cannot deactivate your own account.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'account_status' => 'active',
        ]);
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $staff = $this->createUser(
            role: 'staff',
            status: 'active'
        );

        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $response = $this
            ->actingAs($staff)
            ->patch(
                route('admin.users.status', $admin),
                [
                    'account_status' => 'inactive',
                    'deactivation_reason' => 'Attempt to remove last administrator.',
                ]
            );

        $response->assertRedirect(
            route('admin.users.index')
        );

        $response->assertSessionHas(
            'error',
            'The last active administrator cannot be deactivated.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'account_status' => 'active',
        ]);
    }

    public function test_pending_verification_account_cannot_be_manually_activated(): void
    {
        $admin = $this->createUser(
            role: 'admin',
            status: 'active'
        );

        $target = $this->createUser(
            role: 'customer',
            status: 'pending_verification'
        );

        $response = $this
            ->actingAs($admin)
            ->patch(
                route('admin.users.status', $target),
                [
                    'account_status' => 'active',
                ]
            );

        $response->assertRedirect(
            route('admin.users.index')
        );

        $response->assertSessionHas(
            'error',
            'This account status is controlled by its verification workflow and cannot be changed manually.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'account_status' => 'pending_verification',
        ]);
    }

    public function test_customer_cannot_change_another_users_account_status(): void
    {
        $customer = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $target = $this->createUser(
            role: 'customer',
            status: 'active'
        );

        $this
            ->actingAs($customer)
            ->patch(
                route('admin.users.status', $target),
                [
                    'account_status' => 'inactive',
                    'deactivation_reason' => 'Unauthorized attempt.',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'account_status' => 'active',
        ]);
    }

    private function createUser(
        string $role,
        string $status
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'account_status' => $status,
        ])->save();

        return $user->refresh();
    }
}
