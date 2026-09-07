<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
        ]);

        $latestSubscription = $subscriber->subscriptions->first();

        $allowedStatusTransitions = $this->allowedStatusTransitions(
            $subscriber->status
        );

        return view('admin.subscribers.show', [
            'subscriber' => $subscriber,
            'latestSubscription' => $latestSubscription,
            'allowedStatusTransitions' => $allowedStatusTransitions,
        ]);
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
            /*
             * Lock the current customer row so two administrators cannot
             * perform conflicting status changes at the same time.
             */
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

            /*
             * Pending activation belongs to the installation/service
             * activation workflow, not this manual status control.
             *
             * Disconnected service will later require the proper
             * reconnection workflow.
             */
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
