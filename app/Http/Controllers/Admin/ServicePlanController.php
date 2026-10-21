<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServicePlanRequest;
use App\Http\Requests\UpdateServicePlanRequest;
use App\Models\ServicePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicePlanController extends Controller
{
    public function index(): View
    {
        $servicePlans = ServicePlan::query()
            ->orderByDesc('is_active')
            ->orderBy('speed_mbps')
            ->orderBy('name')
            ->get();

        return view(
            'admin.service-plans.index',
            compact('servicePlans')
        );
    }

    public function create(): View
    {
        return view('admin.service-plans.create');
    }

    public function store(
        StoreServicePlanRequest $request
    ): RedirectResponse {
        ServicePlan::create($request->validated());

        return redirect()
            ->route('admin.service-plans.index')
            ->with(
                'success',
                'Internet package created successfully.'
            );
    }

    public function edit(
        ServicePlan $servicePlan
    ): View {
        $hasExistingReferences = $servicePlan->subscriptions()->exists()
            || $servicePlan->serviceApplications()->exists()
            || $servicePlan->serviceRequests()->exists()
            || $servicePlan->currentPlanChangeRequests()->where('status', 'pending')->exists()
            || $servicePlan->requestedPlanChangeRequests()->where('status', 'pending')->exists();

        return view(
            'admin.service-plans.edit',
            compact('servicePlan', 'hasExistingReferences')
        );
    }

    public function update(
        UpdateServicePlanRequest $request,
        ServicePlan $servicePlan
    ): RedirectResponse {
        $validated = $request->validated();

        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($servicePlan, $validated): array {
            $lockedPlan = ServicePlan::query()
                ->whereKey($servicePlan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasMaterialChange = $lockedPlan->name !== $validated['name']
                || (float) $lockedPlan->speed_mbps !== (float) $validated['speed_mbps']
                || (float) $lockedPlan->monthly_fee !== (float) $validated['monthly_fee']
                || (int) $lockedPlan->duration_months !== (int) $validated['duration_months']
                || (bool) $lockedPlan->is_custom !== (bool) $validated['is_custom'];

            $hasExistingReferences = $lockedPlan->subscriptions()->exists()
                || $lockedPlan->serviceApplications()->exists()
                || $lockedPlan->serviceRequests()->exists()
                || $lockedPlan->currentPlanChangeRequests()->where('status', 'pending')->exists()
                || $lockedPlan->requestedPlanChangeRequests()->where('status', 'pending')->exists();

            if ($hasMaterialChange && $hasExistingReferences) {
                return [
                    'success' => false,
                    'message' => 'This package is already referenced by subscribers, applications, service requests, or pending plan changes. '
                        . 'To change its price, speed, name, or contract terms, create a new package version '
                        . 'and deactivate the old package for new applications. Existing billing history is preserved.',
                ];
            }

            $lockedPlan->update($validated);

            return ['success' => true];
        });

        if (! $result['success']) {
            return redirect()
                ->route('admin.service-plans.edit', $servicePlan)
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.service-plans.index')
            ->with(
                'success',
                'Internet package updated successfully.'
            );
    }

    public function updateStatus(
        ServicePlan $servicePlan
    ): RedirectResponse {
        $servicePlan->update([
            'is_active' => ! $servicePlan->is_active,
        ]);

        return redirect()
            ->route('admin.service-plans.index')
            ->with(
                'success',
                $servicePlan->is_active
                    ? 'Internet package activated successfully.'
                    : 'Internet package deactivated successfully.'
            );
    }
}
