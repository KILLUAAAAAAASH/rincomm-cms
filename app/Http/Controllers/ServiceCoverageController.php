<?php

namespace App\Http\Controllers;

use App\Models\ServiceArea;
use App\Models\ServicePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCoverageController extends Controller
{
    public function create(Request $request): View
    {
        $selectedPlan = null;

        if ($request->filled('plan')) {
            $selectedPlan = ServicePlan::query()
                ->whereKey($request->integer('plan'))
                ->where('is_active', true)
                ->first();
        }

        $coverageAreas = ServiceArea::query()
            ->select([
                'id',
                'province',
                'city_municipality',
                'barangay',
                'postal_code',
                'latitude',
                'longitude',
                'is_serviceable',
            ])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->orderBy('barangay')
            ->get();

        return view('apply.coverage', compact(
            'selectedPlan',
            'coverageAreas'
        ));
    }

    public function check(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'province' => ['bail', 'required', 'string', 'max:255'],
                'city_municipality' => ['bail', 'required', 'string', 'max:255'],
                'barangay' => ['bail', 'required', 'string', 'max:255'],
                'plan_id' => ['nullable', 'integer'],
            ],
            [
                'province.required' => 'Please select or enter your province.',
                'city_municipality.required' => 'Please select or enter your city or municipality.',
                'barangay.required' => 'Please select or enter your barangay.',
            ]
        );

        $serviceArea = ServiceArea::query()
            ->where('province', trim($validated['province']))
            ->where('city_municipality', trim($validated['city_municipality']))
            ->where('barangay', trim($validated['barangay']))
            ->first();

        if (! $serviceArea || ! $serviceArea->is_serviceable) {
            $request->session()->forget([
                'service_application.coverage',
                'service_application.plan_id',
            ]);

            return back()
                ->withInput()
                ->with('coverage_status', 'unavailable');
        }

        $selectedPlan = null;

        if (! empty($validated['plan_id'])) {
            $selectedPlan = ServicePlan::query()
                ->whereKey($validated['plan_id'])
                ->where('is_active', true)
                ->first();

            if (! $selectedPlan) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'plan_id' => 'The selected internet plan is no longer available.',
                    ]);
            }
        }

        $request->session()->put('service_application.coverage', [
            'service_area_id' => $serviceArea->id,
            'province' => $serviceArea->province,
            'city_municipality' => $serviceArea->city_municipality,
            'barangay' => $serviceArea->barangay,
            'postal_code' => $serviceArea->postal_code,
            'checked_at' => now()->toIso8601String(),
        ]);

        if ($selectedPlan) {
            $request->session()->put(
                'service_application.plan_id',
                $selectedPlan->id
            );
        } else {
            $request->session()->forget('service_application.plan_id');
        }

        return back()
            ->withInput()
            ->with('coverage_status', 'available');
    }
}
