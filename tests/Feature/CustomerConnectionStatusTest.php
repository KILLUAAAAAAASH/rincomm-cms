<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerNetworkAccount;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerConnectionStatusTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): Customer
    {
        $this->seed(DemoDataSeeder::class);
        return Customer::query()->with('user')->firstOrFail();
    }

    private function configureRouter(): void
    {
        config()->set('mikrotik', [
            'enabled' => true,
            'base_url' => 'https://router.test',
            'username' => 'read-only',
            'password' => 'not-a-real-password',
            'ca_bundle' => null,
        ]);
        config()->set('cache.default', 'array');
        Cache::flush();
    }

    private function map(Customer $customer, string $username): void
    {
        CustomerNetworkAccount::query()->create([
            'customer_id' => $customer->id,
            'router_key' => 'primary',
            'pppoe_username' => $username,
        ]);
    }

    public function test_dashboard_displays_a_separate_live_pppoe_indicator(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer->user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Service Status')
            ->assertSee('Live PPPoE Connection');
    }

    public function test_without_mapping_the_status_is_unavailable_without_router_call(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        Http::fake();
        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('status', 'unavailable');
        Http::assertNothingSent();
    }

    public function test_disabled_router_is_unavailable_even_with_mapping(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');
        config()->set('mikrotik.enabled', false);
        Http::fake();
        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show'))
            ->assertJsonPath('status', 'unavailable');
        Http::assertNothingSent();
    }

    public function test_valid_pppoe_session_is_online_and_cached(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');
        Http::fake(['https://router.test/*' => Http::response([
            ['name' => 'customer-one', 'service' => 'pppoe'],
        ])]);
        $this->actingAs($customer->user);
        $url = route('customer.connection-status.show');
        $this->get($url)->assertJsonPath('status', 'online');
        $this->get($url)->assertJsonPath('status', 'online');
        Http::assertSentCount(1);
    }

    public function test_no_active_pppoe_session_is_offline(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');
        Http::fake(['https://router.test/*' => Http::response([])]);
        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show'))
            ->assertJsonPath('status', 'offline');
    }

    public function test_router_failure_does_not_claim_subscriber_is_offline(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');
        Http::fake(['https://router.test/*' => Http::response(['error' => 'Unauthorized'], 401)]);
        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show'))
            ->assertJsonPath('status', 'unavailable');
    }

    public function test_only_own_network_account_is_used_not_query_parameter(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');
        Http::fake(['https://router.test/*' => Http::response([
            ['name' => 'other-user', 'service' => 'pppoe'],
        ])]);
        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show', ['pppoe_username' => 'other-user']))
            ->assertJsonPath('status', 'offline')
            ->assertDontSee('customer-one')
            ->assertDontSee('other-user');
    }

    public function test_cache_failure_returns_unavailable_without_router_call(): void
    {
        $customer = $this->customer();
        $this->configureRouter();
        $this->map($customer, 'customer-one');

        Http::fake();

        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \RuntimeException('cache outage'));

        $this->actingAs($customer->user)
            ->get(route('customer.connection-status.show'))
            ->assertOk()
            ->assertJsonPath('status', 'unavailable')
            ->assertJsonPath('checked_at', null);

        Http::assertNothingSent();
    }
    public function test_non_customers_cannot_read_status(): void
    {
        $this->customer();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'account_status' => 'active'])->save();
        $this->actingAs($admin)
            ->get(route('customer.connection-status.show'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_read_status(): void
    {
        $this->get(route('customer.connection-status.show'))
            ->assertRedirect(route('login'));
    }
}
