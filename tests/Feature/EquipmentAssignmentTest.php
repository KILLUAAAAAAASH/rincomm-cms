<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'account_status' => 'active']);
    }

    private function customer(string $code = 'CUST-9091', string $status = 'active'): Customer
    {
        $user = User::factory()->create(['role' => 'customer', 'account_status' => 'active']);

        return Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => 'Demo',
            'last_name' => 'Subscriber',
            'email' => $user->email,
            'phone' => '09'.str_pad(preg_replace('/\D/', '', $code), 9, '0', STR_PAD_LEFT),
            'address' => 'Test address',
            'status' => $status,
        ]);
    }

    private function asset(string $tag = 'RC-ONT-901', ?string $serial = 'SERIAL-901', string $status = 'in_stock'): EquipmentAsset
    {
        return EquipmentAsset::create([
            'asset_tag' => $tag,
            'name' => 'Fiber ONT',
            'category' => 'ont_onu',
            'serial_number' => $serial,
            'status' => $status,
        ]);
    }

    private function input(Customer $customer, EquipmentAsset $asset, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'equipment_asset_id' => $asset->id,
            'ip_address' => '192.168.1.1',
        ], $overrides);
    }

    public function test_staff_assigns_serialized_equipment_and_deploys_asset_with_audit_record(): void
    {
        $staff = $this->staff();
        $customer = $this->customer();
        $asset = $this->asset();

        $this->actingAs($staff)
            ->post(route('admin.equipment-assignments.store'), $this->input($customer, $asset))
            ->assertRedirect(route('admin.equipment-assets.show', $asset));

        $this->assertDatabaseHas('equipment_assets', ['id' => $asset->id, 'status' => 'deployed']);
        $this->assertDatabaseHas('equipment_assignments', [
            'equipment_asset_id' => $asset->id,
            'customer_id' => $customer->id,
            'ip_address' => '192.168.1.1',
            'assigned_by' => $staff->id,
            'released_at' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'equipment.customer_assigned']);
        $this->get(route('admin.equipment-assets.show', $asset))->assertOk()->assertSeeText('CUST-9091');
        $this->get(route('admin.equipment-assignments.index'))->assertOk()->assertSeeText('SERIAL-901');
    }

    public function test_duplicate_active_assignment_is_refused(): void
    {
        $c1 = $this->customer('CUST-9001');
        $c2 = $this->customer('CUST-9002');
        $asset = $this->asset();
        $this->actingAs($this->staff());

        $this->post(route('admin.equipment-assignments.store'), $this->input($c1, $asset))->assertRedirect();
        $this->post(route('admin.equipment-assignments.store'), $this->input($c2, $asset))
            ->assertSessionHasErrors('equipment_asset_id');

        $this->assertSame(1, EquipmentAssignment::count());
        $this->assertSame($c1->id, EquipmentAssignment::firstOrFail()->customer_id);
    }

    public function test_only_in_stock_serialized_assets_can_be_assigned(): void
    {
        $customer = $this->customer();
        $this->actingAs($this->staff());

        $noSerial = $this->asset('RC-TEST-EMPTY', null);
        $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $noSerial))
            ->assertSessionHasErrors('equipment_asset_id');
        $underMaintenance = $this->asset('RC-TEST-MAINT', 'SERIAL-902', 'maintenance');
        $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $underMaintenance))
            ->assertSessionHasErrors('equipment_asset_id');
        $this->assertSame(0, EquipmentAssignment::count());
    }

    public function test_invalid_ip_and_inactive_customer_are_rejected(): void
    {
        $customer = $this->customer('CUST-9999', 'inactive');
        $asset = $this->asset();
        $this->actingAs($this->staff());
        $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $asset))
            ->assertSessionHasErrors('customer_id');
        $customer->update(['status' => 'active']);
        $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $asset, ['ip_address' => 'not-an-ip']))
            ->assertSessionHasErrors('ip_address');
        $this->assertSame(0, EquipmentAssignment::count());
    }

    public function test_customer_and_technician_cannot_access_or_assign_equipment(): void
    {
        $customer = $this->customer();
        $asset = $this->asset();
        foreach (['customer', 'technician'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'account_status' => 'active']);
            $this->actingAs($actor)
                ->get(route('admin.equipment-assignments.index'))->assertForbidden();
            $this->get(route('admin.equipment-assignments.create'))->assertForbidden();
            $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $asset))->assertForbidden();
        }
        $this->assertSame(0, EquipmentAssignment::count());
    }

    public function test_assigned_equipment_cannot_be_manually_returned_to_stock(): void
    {
        $customer = $this->customer();
        $asset = $this->asset();
        $this->actingAs($this->staff());
        $this->post(route('admin.equipment-assignments.store'), $this->input($customer, $asset))->assertRedirect();
        $this->patch(route('admin.equipment-assets.update', $asset), [
            'asset_tag' => $asset->asset_tag,
            'name' => $asset->name,
            'category' => $asset->category,
            'brand' => '',
            'model' => '',
            'serial_number' => $asset->serial_number,
            'status' => 'in_stock',
            'notes' => '',
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('equipment_assets', ['id' => $asset->id, 'status' => 'deployed']);
    }

    public function test_private_ip_address_can_be_used_at_independent_customer_sites(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        $this->post(route('admin.equipment-assignments.store'), $this->input(
            $this->customer('CUST-9010'), $this->asset('RC-ONT-910', 'SN-910')
        ))->assertRedirect();
        $this->post(route('admin.equipment-assignments.store'), $this->input(
            $this->customer('CUST-9011'), $this->asset('RC-ONT-911', 'SN-911')
        ))->assertRedirect();
        $this->assertSame(2, EquipmentAssignment::where('ip_address', '192.168.1.1')->count());
    }
}
