<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceApplication;
use App\Models\ServiceArea;
use App\Models\ServicePlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceApplicationController extends Controller
{
    /**
     * Display submitted service applications for review.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'pending');

        $allowedStatuses = [
            'pending',
            'approved',
            'rejected',
            'cancelled',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $applications = ServiceApplication::query()
            ->select([
                'id',
                'application_number',
                'first_name',
                'middle_name',
                'last_name',
                'phone',
                'email',
                'service_plan_id',
                'service_area_id',
                'status',
                'submitted_at',
                'reviewed_at',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('application_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
            )
            ->where('status', $status)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.applications.index', [
            'applications' => $applications,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Display a submitted service application for review.
     */
    public function show(ServiceApplication $application): View
    {
        abort_if($application->status === 'draft', 404);

        $application->load([
            'user',
            'servicePlan',
            'serviceArea',
            'reviewer',
        ]);

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    /**
     * Approve a pending service application and create subscriber records.
     */
    public function approve(Request $request, ServiceApplication $application): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $application): array {
            $lockedApplication = ServiceApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending applications can be approved.',
                ];
            }

            $user = User::query()
                ->whereKey($lockedApplication->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'The applicant portal account is unavailable.',
                ];
            }

            if ($user->role !== 'customer') {
                return [
                    'success' => false,
                    'message' => 'Only customer accounts can be converted to subscriber records.',
                ];
            }

            if (Customer::query()->where('user_id', $user->id)->exists()) {
                return [
                    'success' => false,
                    'message' => 'This applicant already has a customer record.',
                ];
            }

            $servicePlan = ServicePlan::query()
                ->whereKey($lockedApplication->service_plan_id)
                ->first();

            if (! $servicePlan || ! $servicePlan->is_active) {
                return [
                    'success' => false,
                    'message' => 'The selected service plan is no longer active.',
                ];
            }

            $serviceArea = ServiceArea::query()
                ->whereKey($lockedApplication->service_area_id)
                ->first();

            if (! $serviceArea || ! $serviceArea->is_serviceable) {
                return [
                    'success' => false,
                    'message' => 'The verified installation area is no longer serviceable.',
                ];
            }

            $customerCode = 'CUST-' . str_pad(
                (string) $user->id,
                4,
                '0',
                STR_PAD_LEFT
            );

            if (Customer::query()->where('customer_code', $customerCode)->exists()) {
                return [
                    'success' => false,
                    'message' => 'The generated customer code is already in use.',
                ];
            }

            $customer = Customer::create([
                'user_id' => $user->id,
                'customer_code' => $customerCode,
                'first_name' => $lockedApplication->first_name,
                'middle_name' => $lockedApplication->middle_name,
                'last_name' => $lockedApplication->last_name,
                'phone' => $lockedApplication->phone,
                'email' => $lockedApplication->email,
                'address' => $lockedApplication->installation_address,
                'city' => $serviceArea->city_municipality,
                'province' => $serviceArea->province,
                'postal_code' => $serviceArea->postal_code,
                'billing_address' => $lockedApplication->billing_address,
                'installation_address' => $lockedApplication->installation_address,
                'status' => 'pending',
            ]);

            Subscription::create([
                'customer_id' => $customer->id,
                'service_plan_id' => $servicePlan->id,
                'start_date' => null,
                'end_date' => null,
                'lock_in_months' => (int) $servicePlan->duration_months,
                'discount_amount' => 0,
                'is_custom_plan' => (bool) $servicePlan->is_custom,
                'status' => 'pending',
            ]);

            $lockedApplication->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            return [
                'success' => true,
                'message' => "Application approved. Customer {$customerCode} and a pending subscription were created.",
            ];
        });

        return redirect()
            ->route('admin.applications.show', $application)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Reject a pending service application.
     */
    public function reject(Request $request, ServiceApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request, $application, $validated): array {
            $lockedApplication = ServiceApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedApplication->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending applications can be rejected.',
                ];
            }

            $lockedApplication->update([
                'status' => 'rejected',
                'rejection_reason' => trim($validated['rejection_reason']),
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            return [
                'success' => true,
                'message' => 'Application rejected successfully.',
            ];
        });

        return redirect()
            ->route('admin.applications.show', $application)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
