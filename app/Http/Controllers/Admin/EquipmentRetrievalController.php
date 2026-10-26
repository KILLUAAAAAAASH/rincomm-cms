<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEquipmentRetrievalRequest;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Models\EquipmentRetrieval;
use App\Models\JobOrder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipmentRetrievalController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $query = EquipmentRetrieval::query()->with([
            'assignment.customer', 'assignment.equipmentAsset', 'retrievedBy', 'jobOrder',
        ]);

        if (filled($filters['search'] ?? null)) {
            $term = addcslashes(trim($filters['search']), '%_\\');
            $query->whereHas('assignment', function ($q) use ($term): void {
                $q->whereHas('customer', fn ($c) => $c
                    ->where('customer_code', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%"))
                    ->orWhereHas('equipmentAsset', fn ($a) => $a
                        ->where('asset_tag', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%"));
            });
        }

        return view('admin.equipment-retrievals.index', [
            'retrievals' => $query->orderByDesc('retrieved_on')->orderByDesc('id')
                ->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'assignment_id' => ['nullable', 'integer'],
        ]);

        $available = EquipmentAssignment::query()
            ->with(['customer', 'equipmentAsset'])
            ->whereNull('released_at')
            ->whereHas('equipmentAsset', fn ($q) => $q->where('status', 'deployed'));

        if (filled($filters['search'] ?? null)) {
            $term = addcslashes(trim($filters['search']), '%_\\');
            $available->where(function ($query) use ($term): void {
                $query->whereHas('customer', fn ($q) => $q
                    ->where('customer_code', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%"))
                    ->orWhereHas('equipmentAsset', fn ($q) => $q
                        ->where('asset_tag', 'like', "%{$term}%")
                        ->orWhere('serial_number', 'like', "%{$term}%"));
            });
        }

        $assignments = $available->orderByDesc('id')->limit(40)->get();
        $selected = isset($filters['assignment_id'])
            ? EquipmentAssignment::query()
                ->with(['customer', 'equipmentAsset'])
                ->whereNull('released_at')
                ->whereHas('equipmentAsset', fn ($q) => $q->where('status', 'deployed'))
                ->find($filters['assignment_id'])
            : null;

        $jobOrders = $selected
            ? JobOrder::query()->where('customer_id', $selected->customer_id)
                ->where('status', 'completed')->orderByDesc('id')->limit(25)->get()
            : collect();

        return view('admin.equipment-retrievals.create', compact('filters', 'assignments', 'selected', 'jobOrders'));
    }

    public function store(StoreEquipmentRetrievalRequest $request)
    {
        $data = $request->validated();

        try {
            $retrieval = DB::transaction(function () use ($request, $data): EquipmentRetrieval {
                // Consistent lock order with equipment assignment: lock asset first.
                $snapshot = EquipmentAssignment::query()->findOrFail($data['equipment_assignment_id']);
                $asset = EquipmentAsset::query()->lockForUpdate()->findOrFail($snapshot->equipment_asset_id);
                $assignment = EquipmentAssignment::query()->lockForUpdate()->findOrFail($snapshot->id);

                if ($assignment->equipment_asset_id !== $asset->id ||
                    $assignment->released_at !== null ||
                    $asset->status !== 'deployed' ||
                    EquipmentRetrieval::where('equipment_assignment_id', $assignment->id)->exists()) {
                    throw ValidationException::withMessages([
                        'equipment_assignment_id' => 'This assignment is no longer active or was already retrieved.',
                    ]);
                }

                if ($data['retrieved_on'] < $assignment->assigned_at->toDateString()) {
                    throw ValidationException::withMessages([
                        'retrieved_on' => 'Retrieval cannot be dated before the equipment was assigned.',
                    ]);
                }

                if (! empty($data['job_order_id'])) {
                    $job = JobOrder::query()->find($data['job_order_id']);
                    if (! $job || $job->customer_id !== $assignment->customer_id || $job->status !== 'completed') {
                        throw ValidationException::withMessages([
                            'job_order_id' => 'Job Order must be completed and belong to this subscriber.',
                        ]);
                    }
                }

                $retrieval = EquipmentRetrieval::create([
                    'equipment_assignment_id' => $assignment->id,
                    'job_order_id' => $data['job_order_id'] ?? null,
                    'retrieved_on' => $data['retrieved_on'],
                    'reason' => $data['reason'],
                    'condition' => $data['condition'],
                    'remarks' => $data['remarks'] ?? null,
                    'retrieved_by' => $request->user()->id,
                ]);

                $assignment->update([
                    'released_at' => now(),
                    'released_by' => $request->user()->id,
                ]);

                $newStatus = $data['condition'] === 'usable' ? 'in_stock' : 'maintenance';
                $asset->update(['status' => $newStatus]);

                DB::table('activity_logs')->insert([
                    'actor_user_id' => $request->user()->id,
                    'target_user_id' => $assignment->customer->user_id,
                    'action' => 'equipment.retrieved',
                    'description' => 'Retrieved hardware asset '.$asset->asset_tag.' from subscriber '.$assignment->customer->customer_code,
                    'metadata' => json_encode([
                        'equipment_asset_id' => $asset->id,
                        'equipment_assignment_id' => $assignment->id,
                        'equipment_retrieval_id' => $retrieval->id,
                        'customer_id' => $assignment->customer_id,
                        'job_order_id' => $data['job_order_id'] ?? null,
                        'new_status' => $newStatus,
                        'condition' => $data['condition'],
                        'reason' => $data['reason'],
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $retrieval;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'equipment_assignment_id' => 'This equipment assignment already has a retrieval record.',
            ]);
        }

        return redirect()->route('admin.equipment-retrievals.show', $retrieval)
            ->with('success', 'Physical equipment retrieval recorded successfully.');
    }

    public function show(EquipmentRetrieval $equipmentRetrieval)
    {
        $equipmentRetrieval->load([
            'assignment.customer', 'assignment.equipmentAsset', 'retrievedBy', 'jobOrder',
        ]);

        return view('admin.equipment-retrievals.show', compact('equipmentRetrieval'));
    }
}
