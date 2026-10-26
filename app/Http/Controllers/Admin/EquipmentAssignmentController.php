<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEquipmentAssignmentRequest;
use App\Models\Customer;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', Rule::in(['active', 'history'])],
        ]);

        $assignments = EquipmentAssignment::query()
            ->with(['customer', 'equipmentAsset', 'assignedBy', 'retrieval'])
            ->when(($filters['state'] ?? '') === 'active', fn ($q) => $q->whereNull('released_at'))
            ->when(($filters['state'] ?? '') === 'history', fn ($q) => $q->whereNotNull('released_at'));

        if (filled($filters['search'] ?? null)) {
            $text = addcslashes(trim($filters['search']), '%_\\');
            $assignments->where(function ($q) use ($text): void {
                $q->where('ip_address', 'like', "%{$text}%")
                    ->orWhereHas('equipmentAsset', fn ($a) => $a->where('asset_tag', 'like', "%{$text}%")
                        ->orWhere('serial_number', 'like', "%{$text}%"))
                    ->orWhereHas('customer', fn ($c) => $c->where('customer_code', 'like', "%{$text}%")
                        ->orWhere('first_name', 'like', "%{$text}%")
                        ->orWhere('last_name', 'like', "%{$text}%"));
            });
        }

        return view('admin.equipment-assignments.index', [
            'assignments' => $assignments->orderByDesc('assigned_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $filters = $request->validate([
            'customer_search' => ['nullable', 'string', 'max:120'],
            'asset_search' => ['nullable', 'string', 'max:120'],
            'customer_id' => ['nullable', 'integer'],
            'asset_id' => ['nullable', 'integer'],
        ]);

        $customers = Customer::query()->where('status', 'active');
        if (filled($filters['customer_search'] ?? null)) {
            $q = addcslashes(trim($filters['customer_search']), '%_\\');
            $customers->where(fn ($builder) => $builder
                ->where('customer_code', 'like', "%{$q}%")
                ->orWhere('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%"));
        }
        $customers = $customers->orderBy('customer_code')->limit(40)->get();
        $selectedCustomer = isset($filters['customer_id'])
            ? Customer::where('status', 'active')->find($filters['customer_id'])
            : null;
        if ($selectedCustomer && ! $customers->contains('id', $selectedCustomer->id)) {
            $customers->prepend($selectedCustomer);
        }

        $assets = EquipmentAsset::query()
            ->where('status', 'in_stock')
            ->whereNotNull('serial_number')
            ->where('serial_number', '<>', '')
            ->whereDoesntHave('activeAssignment');
        if (filled($filters['asset_search'] ?? null)) {
            $q = addcslashes(trim($filters['asset_search']), '%_\\');
            $assets->where(fn ($builder) => $builder
                ->where('asset_tag', 'like', "%{$q}%")
                ->orWhere('serial_number', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%"));
        }
        $assets = $assets->orderBy('asset_tag')->limit(40)->get();
        $selectedAsset = isset($filters['asset_id'])
            ? EquipmentAsset::where('status', 'in_stock')
                ->whereNotNull('serial_number')->whereDoesntHave('activeAssignment')
                ->find($filters['asset_id'])
            : null;
        if ($selectedAsset && ! $assets->contains('id', $selectedAsset->id)) {
            $assets->prepend($selectedAsset);
        }

        return view('admin.equipment-assignments.create', compact('customers', 'assets', 'filters'));
    }

    public function store(StoreEquipmentAssignmentRequest $request)
    {
        $data = $request->validated();

        $assignment = DB::transaction(function () use ($request, $data): EquipmentAssignment {
            // Lock the asset, not just an assignment row that may not exist yet.
            // This serializes simultaneous assignment attempts for one device.
            $asset = EquipmentAsset::query()->lockForUpdate()->findOrFail($data['equipment_asset_id']);
            $customer = Customer::query()->findOrFail($data['customer_id']);

            if ($customer->status !== 'active') {
                throw ValidationException::withMessages(['customer_id' => 'Only active subscriber accounts can receive equipment.']);
            }
            if ($asset->status !== 'in_stock' || blank($asset->serial_number)) {
                throw ValidationException::withMessages(['equipment_asset_id' => 'Only in-stock equipment with a serial number can be assigned.']);
            }
            if ($asset->activeAssignment()->exists()) {
                throw ValidationException::withMessages(['equipment_asset_id' => 'This device is already assigned to a subscriber.']);
            }

            $assignment = $asset->assignments()->create([
                'customer_id' => $customer->id,
                'ip_address' => $data['ip_address'] ?? null,
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);
            $asset->update(['status' => 'deployed']);

            DB::table('activity_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'target_user_id' => $customer->user_id,
                'action' => 'equipment.customer_assigned',
                'description' => 'Assigned hardware asset '.$asset->asset_tag.' to subscriber '.$customer->customer_code,
                'metadata' => json_encode([
                    'equipment_asset_id' => $asset->id,
                    'customer_id' => $customer->id,
                    'equipment_assignment_id' => $assignment->id,
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $assignment;
        });

        return redirect()->route('admin.equipment-assets.show', $assignment->equipment_asset_id)
            ->with('success', 'Equipment assigned to the subscriber successfully.');
    }
}
