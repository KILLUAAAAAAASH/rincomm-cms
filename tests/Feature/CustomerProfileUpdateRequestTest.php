<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private function customerUser(string $code): array
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer', 'account_status' => 'active'])->save();
        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => 'Original',
            'last_name' => 'Customer',
            'address' => 'Original Address',
            'phone' => '09120000000',
            'status' => 'active',
        ]);
        return [$user->refresh(), $customer->refresh()];
    }

    private function payload(array $change = []): array
    {
        return array_merge([
            'phone' => '09990000000',
            'address' => 'Original Address',
            'city' => null,
            'province' => null,
            'postal_code' => null,
            'billing_address' => null,
            'reason' => 'Please correct my contact information.',
        ], $change);
    }

    public function test_submission_is_pending_and_does_not_modify_customer_profile(): void
    {
        [$user, $customer] = $this->customerUser('CUST-F5-1');
        $this->actingAs($user)->post(route('customer.profile-update-requests.store'), $this->payload())
            ->assertRedirect(route('customer.profile-update-requests.index'));
        $this->assertDatabaseHas('customer_profile_update_requests', [
            'customer_id' => $customer->id, 'requested_by' => $user->id, 'status' => 'pending',
        ]);
        $this->assertSame('09120000000', $customer->fresh()->phone);
        $this->actingAs($user)->get(route('customer.profile-update-requests.index'))
            ->assertOk()->assertSee('Pending')->assertSee('09990000000');
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        [$user] = $this->customerUser('CUST-F5-2');
        $this->actingAs($user)->post(route('customer.profile-update-requests.store'), $this->payload())
            ->assertRedirect();
        $this->actingAs($user)->post(route('customer.profile-update-requests.store'), $this->payload(['phone' => '09880000000']))
            ->assertSessionHasErrors('request');
        $this->assertDatabaseCount('customer_profile_update_requests', 1);
    }

    public function test_unchanged_or_invalid_request_cannot_be_submitted(): void
    {
        [$user] = $this->customerUser('CUST-F5-3');
        $this->actingAs($user)->post(route('customer.profile-update-requests.store'), $this->payload(['phone' => '09120000000']))
            ->assertSessionHasErrors('request');
        $this->actingAs($user)->post(route('customer.profile-update-requests.store'), $this->payload(['address' => '']))
            ->assertSessionHasErrors('address');
        $this->assertDatabaseCount('customer_profile_update_requests', 0);
    }

    public function test_customer_cannot_view_another_customers_requests(): void
    {
        [$first] = $this->customerUser('CUST-F5-4');
        [$second] = $this->customerUser('CUST-F5-5');
        $this->actingAs($first)->post(route('customer.profile-update-requests.store'), $this->payload(['phone' => '09133333333']));
        $this->actingAs($second)->get(route('customer.profile-update-requests.index'))
            ->assertOk()->assertDontSee('09133333333');
    }

    public function test_guest_staff_and_customer_without_subscriber_cannot_submit(): void
    {
        $this->post(route('customer.profile-update-requests.store'), $this->payload())
            ->assertRedirect(route('login'));

        $staff = User::factory()->create();
        $staff->forceFill(['role' => 'staff', 'account_status' => 'active'])->save();
        $this->actingAs($staff)->post(route('customer.profile-update-requests.store'), $this->payload())->assertForbidden();

        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer', 'account_status' => 'active'])->save();
        $this->actingAs($customer)->get(route('customer.profile-update-requests.index'))
            ->assertOk()->assertSee('subscriber profile has not been');
        $this->actingAs($customer)->post(route('customer.profile-update-requests.store'), $this->payload())->assertForbidden();
    }
}
