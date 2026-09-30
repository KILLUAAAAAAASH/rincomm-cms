<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceArea;
use App\Models\ServicePlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    /**
     * Display the subscriber list.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $statuses = [
            'pending',
            'active',
            'inactive',
            'suspended',
            'disconnected',
        ];

        $subscribers = Customer::query()
            ->select([
                'id',
                'user_id',
                'customer_code',
                'first_name',
                'middle_name',
                'last_name',
                'phone',
                'email',
                'city',
                'province',
                'status',
                'created_at',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw(
                            "CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?",
                            ["%{$search}%"]
                        )
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, $statuses, true),
                fn($query) => $query->where('status', $status)
            )
            ->latest()
            ->get();

        return view('admin.subscribers.index', [
            'subscribers' => $subscribers,
            'search' => $search,
            'status' => $status,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Display a subscriber record.
     */
    public function show(Customer $subscriber): View
    {
        $subscriber->load([
            'user:id,name,email,role,account_status,created_at',

            'documents' => function ($query) {
                $query
                    ->with('uploader:id,name,email')
                    ->latest();
            },

            'subscriptions' => function ($query) {
                $query
                    ->select([
                        'id',
                        'customer_id',
                        'service_plan_id',
                        'start_date',
                        'end_date',
                        'lock_in_months',
                        'discount_amount',
                        'is_custom_plan',
                        'status',
                        'created_at',
                    ])
                    ->with([
                        'servicePlan:id,name,speed_mbps,monthly_fee,duration_months,is_custom,is_active',
                    ])
                    ->latest('id');
            },

            'planChangeRequests' => function ($query) {
                $query
                    ->with([
                        'currentPlan:id,name,speed_mbps,monthly_fee',
                        'requestedPlan:id,name,speed_mbps,monthly_fee',
                        'requester:id,name,email',
                    ])
                    ->latest('id');
            },

            'relocationRequests' => function ($query) {
                $query
                    ->with([
                        'requestedServiceArea:id,province,city_municipality,barangay,postal_code,is_serviceable',
                        'requester:id,name,email',
                        'reviewer:id,name,email',
                    ])
                    ->latest('id');
            },
        ]);

        $latestSubscription = $subscriber->subscriptions->first();

        $activeSubscription = $subscriber->subscriptions
            ->firstWhere('status', 'active');

        $pendingPlanChangeRequest = $subscriber->planChangeRequests
            ->firstWhere('status', 'pending');

        $pendingRelocationRequest = $subscriber->relocationRequests
            ->firstWhere('status', 'pending');

        $availablePlans = collect();

        if ($activeSubscription) {
            $availablePlans = ServicePlan::query()
                ->select([
                    'id',
                    'name',
                    'description',
                    'speed_mbps',
                    'monthly_fee',
                    'duration_months',
                    'is_custom',
                    'is_active',
                ])
                ->where('is_active', true)
                ->whereKeyNot($activeSubscription->service_plan_id)
                ->orderBy('monthly_fee')
                ->orderBy('speed_mbps')
                ->get();
        }

        $serviceableAreas = ServiceArea::query()
            ->select([
                'id',
                'province',
                'city_municipality',
                'barangay',
                'postal_code',
                'is_serviceable',
            ])
            ->where('is_serviceable', true)
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->orderBy('barangay')
            ->get();

        $allowedStatusTransitions = $this->allowedStatusTransitions(
            $subscriber->status
        );

        return view('admin.subscribers.show', [
            'subscriber' => $subscriber,
            'latestSubscription' => $latestSubscription,
            'activeSubscription' => $activeSubscription,
            'pendingPlanChangeRequest' => $pendingPlanChangeRequest,
            'pendingRelocationRequest' => $pendingRelocationRequest,
            'availablePlans' => $availablePlans,
            'serviceableAreas' => $serviceableAreas,
            'allowedStatusTransitions' => $allowedStatusTransitions,
        ]);
    }

    /**
     * Display the subscriber profile edit form.
     */
    public function edit(Customer $subscriber): View
    {
        $subscriber->load([
            'user:id,name,email,role,account_status',
        ]);

        return view('admin.subscribers.edit', [
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Update editable subscriber profile information.
     */
    public function update(
        Request $request,
        Customer $subscriber
    ): RedirectResponse {
        $validated = $request->validate(
            [
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
                    'nullable',
                    'string',
                    'max:255',
                ],
                'address' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'city' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'province' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'postal_code' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'billing_address' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ],
            [
                'first_name.required' => 'First name is required.',
                'first_name.max' => 'First name cannot exceed 255 characters.',

                'middle_name.max' => 'Middle name cannot exceed 255 characters.',

                'last_name.required' => 'Last name is required.',
                'last_name.max' => 'Last name cannot exceed 255 characters.',

                'phone.max' => 'Phone number cannot exceed 255 characters.',

                'address.required' => 'Address is required.',
                'address.max' => 'Address cannot exceed 255 characters.',

                'city.max' => 'City or municipality cannot exceed 255 characters.',
                'province.max' => 'Province cannot exceed 255 characters.',
                'postal_code.max' => 'Postal code cannot exceed 255 characters.',

                'billing_address.max' => 'Billing address cannot exceed 255 characters.',
            ]
        );

        DB::transaction(function () use ($subscriber, $validated) {
            $lockedSubscriber = Customer::query()
                ->with('user')
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSubscriber->fill([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'],
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
            ]);

            $lockedSubscriber->save();

            if ($lockedSubscriber->user) {
                $fullName = trim(
                    implode(' ', array_filter([
                        $lockedSubscriber->first_name,
                        $lockedSubscriber->middle_name,
                        $lockedSubscriber->last_name,
                    ]))
                );

                if ($lockedSubscriber->user->name !== $fullName) {
                    $lockedSubscriber->user->name = $fullName;
                    $lockedSubscriber->user->save();
                }
            }
        });

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with(
                'success',
                'Subscriber profile updated successfully.'
            );
    }

    /**
     * Update a subscriber lifecycle status.
     */
    public function updateStatus(
        Request $request,
        Customer $subscriber
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'status' => [
                    'required',
                    'string',
                    'in:active,inactive,suspended,disconnected',
                ],
                'reason' => [
                    'required',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'status.required' => 'Please select a subscriber status.',
                'status.in' => 'The selected subscriber status is invalid.',
                'reason.required' => 'Please provide a reason for this status change.',
                'reason.max' => 'The status change reason cannot exceed 1000 characters.',
            ]
        );

        $result = DB::transaction(function () use (
            $request,
            $subscriber,
            $validated
        ) {
            $lockedSubscriber = Customer::query()
                ->whereKey($subscriber->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $allowedTransitions = $this->allowedStatusTransitions(
                $lockedSubscriber->status
            );

            if (
                ! in_array(
                    $validated['status'],
                    $allowedTransitions,
                    true
                )
            ) {
                return [
                    'success' => false,
                    'message' => $this->invalidTransitionMessage(
                        $lockedSubscriber->status
                    ),
                ];
            }

            $previousStatus = $lockedSubscriber->status;
            $newStatus = $validated['status'];

            $lockedSubscriber->status = $newStatus;
            $lockedSubscriber->save();

            $lockedSubscriber->statusHistories()->create([
                'changed_by' => $request->user()->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $validated['reason'],
            ]);

            return [
                'success' => true,
                'message' => 'Subscriber status updated successfully.',
            ];
        });

        if (! $result['success']) {
            return redirect()
                ->route('admin.subscribers.show', $subscriber)
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with('success', $result['message']);
    }

    /**
     * Return valid manual status transitions for the current lifecycle state.
     */
    private function allowedStatusTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'active' => [
                'inactive',
                'suspended',
                'disconnected',
            ],

            'inactive' => [
                'active',
                'suspended',
                'disconnected',
            ],

            'suspended' => [
                'active',
                'inactive',
                'disconnected',
            ],

            'pending',
            'disconnected' => [],

            default => [],
        };
    }

    /**
     * Return a clear message when a transition is blocked.
     */
    private function invalidTransitionMessage(string $currentStatus): string
    {
        return match ($currentStatus) {
            'pending' =>
            'Pending subscribers cannot be manually changed from this screen. Complete the installation and service activation workflow first.',

            'disconnected' =>
            'Disconnected subscribers cannot be reactivated from this screen. A proper reconnection workflow is required.',

            default =>
            'The selected subscriber status change is not allowed from the current lifecycle state.',
        };
    }
}
