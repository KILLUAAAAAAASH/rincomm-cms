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
        return view(
            'admin.service-plans.edit',
            compact('servicePlan')
        );
    }

    public function update(
        UpdateServicePlanRequest $request,
        ServicePlan $servicePlan
    ): RedirectResponse {
        $servicePlan->update($request->validated());

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
