<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile_and_user_name_stays_in_sync(): void
    {
        [$user, $customer] = $this->createCustomerUser();

        $response = $this
            ->actingAs($user)
            ->patch(
                route('profile.update'),
                [
                    'first_name' => 'Ada',
                    'middle_name' => 'Byron',
                    'last_name' => 'Lovelace',
                ]
            );

        $response->assertRedirect(
            route('profile.edit')
        );

        $response->assertSessionHas(
            'success',
            'Profile updated successfully.'
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ada Byron Lovelace',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Ada',
            'middle_name' => 'Byron',
            'last_name' => 'Lovelace',
        ]);
    }

    public function test_customer_profile_update_normalizes_blank_middle_name_to_null(): void
    {
        [$user, $customer] = $this->createCustomerUser();

        $response = $this
            ->actingAs($user)
            ->patch(
                route('profile.update'),
                [
                    'first_name' => 'Grace',
                    'middle_name' => '   ',
                    'last_name' => 'Hopper',
                ]
            );

        $response->assertRedirect(
            route('profile.edit')
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Grace Hopper',
        ]);

        $customer->refresh();

        $this->assertSame(
            'Grace',
            $customer->first_name
        );

        $this->assertNull(
            $customer->middle_name
        );

        $this->assertSame(
            'Hopper',
            $customer->last_name
        );
    }

    public function test_invalid_customer_profile_update_does_not_change_profile(): void
    {
        [$user, $customer] = $this->createCustomerUser();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(
                route('profile.update'),
                [
                    'first_name' => '',
                    'middle_name' => 'Q',
                    'last_name' => 'Customer',
                ]
            );

        $response->assertRedirect(
            route('profile.edit')
        );

        $response->assertSessionHasErrors(
            'first_name'
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Original Customer',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Original',
            'middle_name' => null,
            'last_name' => 'Customer',
        ]);
    }

    public function test_non_customer_user_can_update_account_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Staff Name',
        ]);

        $user->forceFill([
            'role' => 'staff',
            'account_status' => 'active',
        ])->save();

        $response = $this
            ->actingAs($user)
            ->patch(
                route('profile.update'),
                [
                    'name' => 'Updated Staff Name',
                ]
            );

        $response->assertRedirect(
            route('profile.edit')
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Staff Name',
        ]);
    }

    public function test_inactive_user_cannot_update_profile(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => 'staff',
            'account_status' => 'inactive',
        ])->save();

        $this
            ->actingAs($user)
            ->patch(
                route('profile.update'),
                [
                    'name' => 'Should Not Change',
                ]
            )
            ->assertForbidden();
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $this
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    private function createCustomerUser(): array
    {
        $user = User::factory()->create([
            'name' => 'Original Customer',
        ]);

        $user->forceFill([
            'role' => 'customer',
            'account_status' => 'active',
        ])->save();

        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => 'CUST-PROFILE-' . $user->id,
            'first_name' => 'Original',
            'middle_name' => null,
            'last_name' => 'Customer',
            'address' => 'Test Address',
            'status' => 'active',
        ]);

        return [
            $user->refresh(),
            $customer->refresh(),
        ];
    }
}
