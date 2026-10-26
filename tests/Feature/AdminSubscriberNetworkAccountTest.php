<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerNetworkAccount;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriberNetworkAccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoDataSeeder::class);
        $this->subscriber = Customer::query()->firstOrFail();
    }

    private function signInAs(string $role): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'account_status' => 'active'])->save();
        $this->actingAs($user->refresh());
    }

    public function test_admin_can_view_mapping_page(): void
    {
        $this->signInAs('admin');

        $this->get(route('admin.subscribers.network-account.show', $this->subscriber))
            ->assertOk()
            ->assertSee('PPPoE Network Account')
            ->assertSee('Not assigned');
    }

    public function test_admin_can_create_a_mapping_without_changing_subscription_state(): void
    {
        $this->signInAs('admin');
        $previousStatus = $this->subscriber->status;

        $this->patch(route('admin.subscribers.network-account.update', $this->subscriber), [
            'pppoe_username' => 'rincomm.thesis1',
        ])->assertRedirect(route('admin.subscribers.network-account.show', $this->subscriber));

        $this->assertDatabaseHas('customer_network_accounts', [
            'customer_id' => $this->subscriber->id,
            'router_key' => 'primary',
            'pppoe_username' => 'rincomm.thesis1',
        ]);
        $this->assertSame($previousStatus, $this->subscriber->fresh()->status);
    }

    public function test_admin_can_update_mapping_without_creating_duplicate(): void
    {
        $this->signInAs('admin');
        $account = CustomerNetworkAccount::query()->create([
            'customer_id' => $this->subscriber->id,
            'router_key' => 'primary',
            'pppoe_username' => 'first-login',
        ]);

        $this->patch(route('admin.subscribers.network-account.update', $this->subscriber), [
            'pppoe_username' => 'second-login',
        ])->assertRedirect();

        $this->assertSame('second-login', $account->fresh()->pppoe_username);
        $this->assertSame(1, CustomerNetworkAccount::query()->count());
    }

    public function test_invalid_login_is_rejected(): void
    {
        $this->signInAs('admin');

        $this->from(route('admin.subscribers.network-account.show', $this->subscriber))
            ->patch(route('admin.subscribers.network-account.update', $this->subscriber), [
                'pppoe_username' => 'invalid with spaces',
            ])->assertSessionHasErrors('pppoe_username');

        $this->assertDatabaseCount('customer_network_accounts', 0);
    }

    public function test_admin_cannot_assign_a_login_claimed_by_another_subscriber(): void
    {
        $this->signInAs('admin');
        $otherUser = User::factory()->create();
        $otherUser->forceFill(['role' => 'customer', 'account_status' => 'active'])->save();
        $other = Customer::query()->create([
            'user_id' => $otherUser->id,
            'customer_code' => 'CUST-NET-0002',
            'first_name' => 'Other',
            'last_name' => 'Subscriber',
            'address' => 'Sample Address',
            'status' => 'pending',
        ]);
        CustomerNetworkAccount::query()->create([
            'customer_id' => $other->id,
            'router_key' => 'primary',
            'pppoe_username' => 'already-used',
        ]);

        $this->patch(route('admin.subscribers.network-account.update', $this->subscriber), [
            'pppoe_username' => 'already-used',
        ])->assertSessionHasErrors('pppoe_username');

        $this->assertDatabaseMissing('customer_network_accounts', [
            'customer_id' => $this->subscriber->id,
            'pppoe_username' => 'already-used',
        ]);
    }

    public function test_admin_can_remove_mapping_without_deleting_customer(): void
    {
        $this->signInAs('admin');
        CustomerNetworkAccount::query()->create([
            'customer_id' => $this->subscriber->id,
            'router_key' => 'primary',
            'pppoe_username' => 'remove-me',
        ]);

        $this->delete(route('admin.subscribers.network-account.destroy', $this->subscriber))
            ->assertRedirect();

        $this->assertDatabaseCount('customer_network_accounts', 0);
        $this->assertDatabaseHas('customers', ['id' => $this->subscriber->id]);
    }

    public function test_non_admins_cannot_view_or_change_mappings(): void
    {
        foreach (['staff', 'customer', 'technician'] as $role) {
            $this->signInAs($role);
            $url = route('admin.subscribers.network-account.show', $this->subscriber);
            $update = route('admin.subscribers.network-account.update', $this->subscriber);
            $remove = route('admin.subscribers.network-account.destroy', $this->subscriber);

            $this->get($url)->assertForbidden();
            $this->patch($update, ['pppoe_username' => 'forbidden'])->assertForbidden();
            $this->delete($remove)->assertForbidden();
        }

        $this->assertDatabaseCount('customer_network_accounts', 0);
    }
}
