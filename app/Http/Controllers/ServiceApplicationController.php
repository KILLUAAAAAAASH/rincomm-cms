<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ServiceApplication;
use App\Models\ServiceArea;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceApplicationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // Existing subscribers should not enter the new application flow.
        $existingCustomer = Customer::query()
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($existingCustomer) {
            return redirect()
                ->route('customer.dashboard')
                ->with(
                    'error',
                    'Your account is already registered as a Rincomm subscriber.'
                );
        }

        $coverage = $request->session()->get(
            'service_application.coverage'
        );

        $planId = $request->session()->get(
            'service_application.plan_id'
        );

        // Coverage checking and plan selection must be completed first.
        if (
            ! is_array($coverage) ||
            empty($coverage['service_area_id']) ||
            empty($planId)
        ) {
            return redirect()
                ->route('apply.coverage')
                ->with(
                    'error',
                    'Please check service availability and select an internet plan before continuing.'
                );
        }

        // Check the current database records instead of trusting session data alone.
        $serviceArea = ServiceArea::query()
            ->whereKey($coverage['service_area_id'])
            ->where('is_serviceable', true)
            ->first();

        $servicePlan = ServicePlan::query()
            ->whereKey($planId)
            ->where('is_active', true)
            ->first();

        if (! $serviceArea || ! $servicePlan) {
            $request->session()->forget([
                'service_application.coverage',
                'service_application.plan_id',
            ]);

            return redirect()
                ->route('apply.coverage')
                ->with(
                    'error',
                    'Your coverage or selected internet plan is no longer available. Please check again.'
                );
        }

        // Only one draft or pending application is allowed per account.
        $existingApplication = ServiceApplication::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [
                'draft',
                'pending',
            ])
            ->latest()
            ->first();

        if ($existingApplication) {
            return redirect()
                ->route('customer.dashboard')
                ->with(
                    'error',
                    'You already have a service application in progress.'
                );
        }

        return view('apply.application', [
            'coverage' => $coverage,
            'serviceArea' => $serviceArea,
            'servicePlan' => $servicePlan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Existing subscribers cannot submit a new-subscriber application.
        if (
            Customer::query()
            ->where('user_id', $request->user()->id)
            ->exists()
        ) {
            return redirect()
                ->route('customer.dashboard')
                ->with(
                    'error',
                    'Your account is already registered as a Rincomm subscriber.'
                );
        }

        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'installation_address' => [
                'required',
                'string',
                'max:1000',
            ],
            'billing_address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $coverage = $request->session()->get(
            'service_application.coverage'
        );

        $planId = $request->session()->get(
            'service_application.plan_id'
        );

        if (
            ! is_array($coverage) ||
            empty($coverage['service_area_id']) ||
            empty($planId)
        ) {
            return redirect()
                ->route('apply.coverage')
                ->with(
                    'error',
                    'Your service application session has expired. Please check service availability again.'
                );
        }

        // Recheck coverage and plan availability before creating the application.
        $serviceArea = ServiceArea::query()
            ->whereKey($coverage['service_area_id'])
            ->where('is_serviceable', true)
            ->first();

        $servicePlan = ServicePlan::query()
            ->whereKey($planId)
            ->where('is_active', true)
            ->first();

        if (! $serviceArea || ! $servicePlan) {
            $request->session()->forget([
                'service_application.coverage',
                'service_application.plan_id',
            ]);

            return redirect()
                ->route('apply.coverage')
                ->with(
                    'error',
                    'Your coverage or selected internet plan is no longer available. Please check again.'
                );
        }

        DB::transaction(function () use (
            $request,
            $validated,
            $serviceArea,
            $servicePlan
        ): void {
            // Lock the account while checking for duplicate submissions.
            User::query()
                ->whereKey($request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingCustomer = Customer::query()
                ->where('user_id', $request->user()->id)
                ->exists();

            if ($existingCustomer) {
                throw ValidationException::withMessages([
                    'application' => 'Your account is already registered as a Rincomm subscriber.',
                ]);
            }

            $existingApplication = ServiceApplication::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', [
                    'draft',
                    'pending',
                ])
                ->exists();

            if ($existingApplication) {
                throw ValidationException::withMessages([
                    'application' => 'You already have a service application in progress.',
                ]);
            }

            ServiceApplication::create([
                'application_number' => 'APP-' . Str::ulid(),
                'user_id' => $request->user()->id,
                'service_area_id' => $serviceArea->id,
                'service_plan_id' => $servicePlan->id,

                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],

                'phone' => $validated['phone'],
                'email' => $validated['email'],

                'installation_address' => $validated['installation_address'],
                'billing_address' => $validated['billing_address'] ?? null,

                'status' => 'pending',
                'submitted_at' => now(),
            ]);
        });

        // Clear the application session only after a successful submission.
        $request->session()->forget([
            'service_application.coverage',
            'service_application.plan_id',
        ]);

        return redirect()
            ->route('customer.dashboard')
            ->with(
                'success',
                'Your service application has been submitted and is waiting for Rincomm review.'
            );
    }
}
