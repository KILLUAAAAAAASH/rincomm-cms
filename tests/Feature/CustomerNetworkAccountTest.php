<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerNetworkAccount;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerNetworkAccountTest extends TestCase
{
    use RefreshDatabase;

    private function exampleCustomer(): Customer
    {
        $this->seed(DemoDataSeeder::class);

        return Customer::query()->firstOrFail();
    }

    public function test_pppoe_mapping_can_be_stored_without_a_password(): void
    {
        $customer = $this->exampleCustomer();

        $account = CustomerNetworkAccount::query()->create([
            'customer_id' => $customer->id,
            'router_key' => 'primary',
            'pppoe_username' => 'thesis-customer-01',
        ]);

        $this->assertSame('thesis-customer-01', $account->fresh()->pppoe_username);
        $this->assertSame($customer->id, $account->customer->id);
        $this->assertFalse(Schema::hasColumn('customer_network_accounts', 'password'));
    }

    public function test_a_customer_cannot_have_two_mappings_for_the_same_router(): void
    {
        $customer = $this->exampleCustomer();

        CustomerNetworkAccount::query()->create([
            'customer_id' => $customer->id,
            'router_key' => 'primary',
            'pppoe_username' => 'thesis-customer-01',
        ]);

        $this->expectException(QueryException::class);

        CustomerNetworkAccount::query()->create([
            'customer_id' => $customer->id,
            'router_key' => 'primary',
            'pppoe_username' => 'thesis-customer-02',
        ]);
    }

    public function test_the_same_username_can_be_used_on_separate_routers(): void
    {
        $customer = $this->exampleCustomer();

        foreach (['primary', 'backup'] as $routerKey) {
            CustomerNetworkAccount::query()->create([
                'customer_id' => $customer->id,
                'router_key' => $routerKey,
                'pppoe_username' => 'thesis-customer-01',
            ]);
        }

        $this->assertSame(2, CustomerNetworkAccount::query()->count());
    }

    public function test_pppoe_username_is_required_by_the_database(): void
    {
        $customer = $this->exampleCustomer();

        $this->expectException(QueryException::class);

        CustomerNetworkAccount::query()->create([
            'customer_id' => $customer->id,
            'router_key' => 'primary',
            'pppoe_username' => null,
        ]);
    }
}
