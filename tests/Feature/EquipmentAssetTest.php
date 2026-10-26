<?php

namespace Tests\Feature;

use App\Models\EquipmentAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquipmentAssetTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'account_status' => 'active']);
    }

    private function fields(array $overrides = []): array
    {
        return array_merge([
            'asset_tag' => 'RC-ONT-0001',
            'name' => 'Fiber ONT',
            'category' => 'ont_onu',
            'brand' => 'Sample Brand',
            'model' => 'Model A',
            'serial_number' => 'SN-10001',
            'status' => 'in_stock',
            'notes' => 'Received from supplier',
        ], $overrides);
    }

    public function test_staff_can_register_and_view_hardware_with_an_audit_entry(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.equipment-assets.store'), $this->fields())
            ->assertRedirect();

        $asset = EquipmentAsset::firstOrFail();
        $this->assertSame('RC-ONT-0001', $asset->asset_tag);
        $this->assertDatabaseHas('activity_logs', ['action' => 'equipment.asset_created']);

        $this->get(route('admin.equipment-assets.show', $asset))
            ->assertOk()
            ->assertSeeText('RC-ONT-0001');
    }

    public function test_duplicate_asset_tag_and_serial_number_are_rejected(): void
    {
        $asset = EquipmentAsset::create($this->fields());

        $this->actingAs($this->staff())
            ->post(route('admin.equipment-assets.store'), $this->fields([
                'serial_number' => 'SN-10002',
            ]))
            ->assertSessionHasErrors('asset_tag');

        $this->post(route('admin.equipment-assets.store'), $this->fields([
            'asset_tag' => 'RC-ONT-0002',
        ]))->assertSessionHasErrors('serial_number');

        $this->assertSame(1, EquipmentAsset::count());
    }

    public function test_staff_can_update_status_and_preserve_record(): void
    {
        $asset = EquipmentAsset::create($this->fields());

        $this->actingAs($this->staff())
            ->patch(route('admin.equipment-assets.update', $asset), $this->fields([
                'status' => 'faulty',
            ]))->assertRedirect(route('admin.equipment-assets.show', $asset));

        $this->assertDatabaseHas('equipment_assets', ['id' => $asset->id, 'status' => 'faulty']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'equipment.asset_updated']);
    }

    public function test_customer_and_technician_cannot_access_inventory(): void
    {
        foreach (['customer', 'technician'] as $role) {
            $user = User::factory()->create(['role' => $role, 'account_status' => 'active']);
            $this->actingAs($user)
                ->get(route('admin.equipment-assets.index'))
                ->assertForbidden();
            $this->post(route('admin.equipment-assets.store'), $this->fields())
                ->assertForbidden();
        }
        $this->assertSame(0, EquipmentAsset::count());
    }

    public function test_search_status_filter_and_pagination_work(): void
    {
        EquipmentAsset::create($this->fields());
        EquipmentAsset::create($this->fields([
            'asset_tag' => 'RC-ROUTER-0002',
            'name' => 'Router B',
            'serial_number' => 'SN-10002',
            'category' => 'router',
            'status' => 'faulty',
        ]));

        $this->actingAs($this->staff())
            ->get(route('admin.equipment-assets.index', ['status' => 'faulty', 'search' => 'Router']))
            ->assertOk()
            ->assertSeeText('RC-ROUTER-0002')
            ->assertDontSeeText('RC-ONT-0001');
    }

    public function test_invalid_status_and_unexpected_columns_cannot_be_saved(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.equipment-assets.store'), $this->fields([
                'status' => 'deployed',
                'customer_id' => 123,
            ]))->assertSessionHasErrors('status');

        $this->post(route('admin.equipment-assets.store'), $this->fields([
            'status' => 'in_stock',
            'customer_id' => 123,
        ]))->assertRedirect();

        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('equipment_assets', 'customer_id'));
    }
}
