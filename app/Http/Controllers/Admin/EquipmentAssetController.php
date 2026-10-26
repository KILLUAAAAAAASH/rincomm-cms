<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEquipmentAssetRequest;
use App\Models\EquipmentAsset;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentAssetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(EquipmentAsset::STATUSES))],
            'category' => ['nullable', Rule::in(array_keys(EquipmentAsset::CATEGORIES))],
        ]);

        $query = EquipmentAsset::query();

        if (filled($filters['search'] ?? null)) {
            $search = trim($filters['search']);
            $escaped = addcslashes($search, '%_\\');

            $query->where(function ($q) use ($escaped): void {
                $q->where('asset_tag', 'like', "%{$escaped}%")
                    ->orWhere('name', 'like', "%{$escaped}%")
                    ->orWhere('serial_number', 'like', "%{$escaped}%")
                    ->orWhere('brand', 'like', "%{$escaped}%")
                    ->orWhere('model', 'like', "%{$escaped}%");
            });
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['category'] ?? null)) {
            $query->where('category', $filters['category']);
        }

        return view('admin.equipment-assets.index', [
            'assets' => $query->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return view('admin.equipment-assets.create');
    }

    public function store(SaveEquipmentAssetRequest $request)
    {
        try {
            $asset = DB::transaction(function () use ($request): EquipmentAsset {
                $asset = EquipmentAsset::create($request->validated());
                $this->recordActivity($request, $asset, 'equipment.asset_created', ['status' => $asset->status]);

                return $asset;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'asset_tag' => 'The asset tag or serial number is already registered.',
            ]);
        }

        return redirect()->route('admin.equipment-assets.show', $asset)
            ->with('success', 'Hardware asset registered successfully.');
    }

    public function show(EquipmentAsset $equipmentAsset)
    {
        $activities = DB::table('activity_logs')
            ->leftJoin('users', 'users.id', '=', 'activity_logs.actor_user_id')
            ->whereIn('activity_logs.action', ['equipment.asset_created', 'equipment.asset_updated', 'equipment.customer_assigned'])
            ->where('activity_logs.metadata->equipment_asset_id', $equipmentAsset->id)
            ->orderByDesc('activity_logs.id')
            ->limit(20)
            ->get(['activity_logs.id', 'activity_logs.action', 'activity_logs.description', 'activity_logs.created_at', 'users.name as actor_name']);

        $assignments = $equipmentAsset->assignments()->with(['customer', 'assignedBy'])->latest('assigned_at')->limit(50)->get();

        return view('admin.equipment-assets.show', compact('equipmentAsset', 'activities', 'assignments'));
    }

    public function edit(EquipmentAsset $equipmentAsset)
    {
        return view('admin.equipment-assets.edit', compact('equipmentAsset'));
    }

    public function update(SaveEquipmentAssetRequest $request, EquipmentAsset $equipmentAsset)
    {
        $changed = false;

        try {
            DB::transaction(function () use ($request, $equipmentAsset, &$changed): void {
                $asset = EquipmentAsset::query()->lockForUpdate()->findOrFail($equipmentAsset->id);
                $asset->fill($request->validated());

                $hasActiveAssignment = $asset->activeAssignment()->exists();
                if ($hasActiveAssignment && (
                    $asset->status !== 'deployed' ||
                    $asset->asset_tag !== $asset->getOriginal('asset_tag') ||
                    $asset->serial_number !== $asset->getOriginal('serial_number')
                )) {
                    throw ValidationException::withMessages([
                        'status' => 'Assigned equipment cannot change status, asset tag, or serial number until retrieved.',
                    ]);
                }

                if (! $hasActiveAssignment && $asset->status === 'deployed') {
                    throw ValidationException::withMessages([
                        'status' => 'Only a customer assignment can mark equipment as deployed.',
                    ]);
                }

                if (! $asset->isDirty()) {
                    return;
                }

                $fields = array_keys($asset->getDirty());
                $oldStatus = $asset->getOriginal('status');
                $asset->save();
                $changed = true;

                $this->recordActivity($request, $asset, 'equipment.asset_updated', [
                    'changed_fields' => $fields,
                    'previous_status' => $oldStatus,
                    'current_status' => $asset->status,
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'asset_tag' => 'The asset tag or serial number is already registered.',
            ]);
        }

        return redirect()->route('admin.equipment-assets.show', $equipmentAsset)
            ->with('success', $changed ? 'Hardware asset updated successfully.' : 'No changes were needed.');
    }

    private function recordActivity(Request $request, EquipmentAsset $asset, string $action, array $details): void
    {
        DB::table('activity_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'target_user_id' => null,
            'action' => $action,
            'description' => $action === 'equipment.asset_created'
                ? 'Registered hardware asset '.$asset->asset_tag
                : 'Updated hardware asset '.$asset->asset_tag,
            'metadata' => json_encode(['equipment_asset_id' => $asset->id] + $details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
