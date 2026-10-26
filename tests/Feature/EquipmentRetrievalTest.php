<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Models\EquipmentRetrieval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentRetrievalTest extends TestCase
{
    use RefreshDatabase;

    private function employee(string $role = 'staff'): User
    {
        return User::factory()->create(['role' => $role, 'account_status' => 'active']);
    }

    private function subscriber(string $code = 'CUST-8801'): Customer
    {
        $user = $this->employee('customer');

        return Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => 'Return',
            'last_name' => 'Example',
            'email' => $user->email,
            'phone' => '09'.str_pad(preg_replace('/\D/', '', $code), 9, '0', STR_PAD_LEFT),
            'address' => 'Example Street',
            'status' => 'active',
        ]);
    }

    private function deployed(Customer $customer, string $tag = 'RIN-RET-001', string $serial = 'SN-RET-001'): array
    {
        $asset = EquipmentAsset::create([
            'asset_tag' => $tag,
            'name' => 'Fiber ONT',
            'category' => 'ont_onu',
            'serial_number' => $serial,
            'status' => 'deployed',
        ]);
        $assignment = EquipmentAssignment::create([
            'equipment_asset_id' => $asset->id,
            'customer_id' => $customer->id,
            'ip_address' => '192.168.1.10',
            'assigned_at' => now()->subDay(),
            'assigned_by' => $this->employee()->id,
        ]);

        return [$asset, $assignment];
    }

    private function payload(EquipmentAssignment $assignment, array $override = []): array
    {
        return array_merge([
            'equipment_assignment_id' => $assignment->id,
            'retrieved_on' => now()->toDateString(),
            'reason' => 'disconnection',
            'condition' => 'usable',
            'remarks' => 'Physical ONT received and tested.',
        ], $override);
    }

    public function test_staff_can_retrieve_usable_device_and_preserve_assignment_history(): void
    {
        $staff = $this->employee();
        $customer = $this->subscriber();
        [$asset, $assignment] = $this->deployed($customer);
        $this->actingAs($staff);

        $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))
            ->assertRedirect();

        $this->assertDatabaseHas('equipment_assets', ['id' => $asset->id, 'status' => 'in_stock']);
        $this->assertDatabaseHas('equipment_assignments', [
            'id' => $assignment->id, 'customer_id' => $customer->id, 'ip_address' => '192.168.1.10',
            'released_by' => $staff->id,
        ]);
        $this->assertNotNull($assignment->fresh()->released_at);
        $this->assertDatabaseHas('equipment_retrievals', [
            'equipment_assignment_id' => $assignment->id,
            'reason' => 'disconnection', 'condition' => 'usable', 'retrieved_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'equipment.retrieved']);
        $this->get(route('admin.equipment-retrievals.index'))->assertOk()->assertSeeText('CUST-8801');
        $this->get(route('admin.equipment-retrievals.show', EquipmentRetrieval::firstOrFail()))
            ->assertOk()->assertSeeText('SN-RET-001')->assertSeeText('192.168.1.10');
    }

    public function test_unverified_or_damaged_device_remains_under_maintenance(): void
    {
        $this->actingAs($this->employee());
        foreach (['needs_inspection', 'damaged'] as $i => $condition) {
            $customer = $this->subscriber('CUST-882'.($i + 1));
            [$asset, $assignment] = $this->deployed($customer, 'RIN-RET-00'.($i + 2), 'SN-RET-00'.($i + 2));
            $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment, [
                'condition' => $condition, 'reason' => 'replacement',
            ]))->assertRedirect();
            $this->assertSame('maintenance', $asset->fresh()->status);
            $this->assertNotNull($assignment->fresh()->released_at);
        }
    }

    public function test_same_assignment_cannot_be_retrieved_twice(): void
    {
        $this->actingAs($this->employee());
        [, $assignment] = $this->deployed($this->subscriber());
        $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))->assertRedirect();
        $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))
            ->assertSessionHasErrors('equipment_assignment_id');
        $this->assertSame(1, EquipmentRetrieval::count());
    }

    public function test_invalid_dates_and_conditions_do_not_close_assignment(): void
    {
        $this->actingAs($this->employee());
        [$asset, $assignment] = $this->deployed($this->subscriber());
        $cases = [
            ['retrieved_on' => now()->addDay()->toDateString()],
            ['retrieved_on' => now()->subDays(2)->toDateString()],
            ['condition' => 'lost'],
            ['reason' => 'unknown'],
            ['job_order_id' => 999999],
        ];
        foreach ($cases as $override) {
            $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment, $override))
                ->assertSessionHasErrors();
        }
        $this->assertSame(0, EquipmentRetrieval::count());
        $this->assertNull($assignment->fresh()->released_at);
        $this->assertSame('deployed', $asset->fresh()->status);
    }

    public function test_customer_and_technician_cannot_access_retrievals(): void
    {
        [, $assignment] = $this->deployed($this->subscriber());
        foreach (['customer', 'technician'] as $role) {
            $this->actingAs($this->employee($role));
            $this->get(route('admin.equipment-retrievals.index'))->assertForbidden();
            $this->get(route('admin.equipment-retrievals.create'))->assertForbidden();
            $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))->assertForbidden();
        }
        $this->assertSame(0, EquipmentRetrieval::count());
    }

    public function test_only_active_deployed_assignments_are_shown_for_retrieval(): void
    {
        $this->actingAs($this->employee());
        [$asset, $assignment] = $this->deployed($this->subscriber());
        $this->get(route('admin.equipment-retrievals.create', ['assignment_id' => $assignment->id]))
            ->assertOk()->assertSeeText('SN-RET-001')->assertSeeText('Confirm Equipment Retrieval');
        $asset->update(['status' => 'maintenance']);
        $this->get(route('admin.equipment-retrievals.create', ['assignment_id' => $assignment->id]))
            ->assertOk()->assertDontSee('name="equipment_assignment_id" value="'.$assignment->id.'"', false);
        $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))
            ->assertSessionHasErrors('equipment_assignment_id');
        $this->assertSame(0, EquipmentRetrieval::count());
    }

    public function test_retrieved_asset_can_be_reassigned_without_erasing_old_history(): void
    {
        $this->actingAs($this->employee());
        $customer = $this->subscriber();
        [$asset, $assignment] = $this->deployed($customer);
        $this->post(route('admin.equipment-retrievals.store'), $this->payload($assignment))->assertRedirect();
        $otherCustomer = $this->subscriber('CUST-8802');
        $this->post(route('admin.equipment-assignments.store'), [
            'customer_id' => $otherCustomer->id,
            'equipment_asset_id' => $asset->id,
            'ip_address' => '192.168.1.11',
        ])->assertRedirect();
        $this->assertSame(2, EquipmentAssignment::where('equipment_asset_id', $asset->id)->count());
        $this->assertSame(1, EquipmentRetrieval::count());
        $this->assertSame('deployed', $asset->fresh()->status);
        $this->assertSame($customer->id, $assignment->fresh()->customer_id);
    }
}
