<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobOrderController extends Controller
{
    /**
     * Display the operational Job Order workspace.
     *
     * The index supplies everything required for:
     * - Job Order list
     * - Create Job Order modal
     * - View Job Order modal
     * - Technician assignment modal
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $jobType = (string) $request->query('job_type', '');

        $statuses = [
            'pending',
            'assigned',
            'in_progress',
            'completed',
            'cancelled',
        ];

        $jobTypes = $this->jobTypes();

        $jobOrders = JobOrder::query()
            ->with([
                'customer',
                'serviceRequest',
                'technician.user',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where(
                                'job_order_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'customer',
                                function ($query) use ($search) {
                                    $query
                                        ->where(
                                            'customer_code',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'first_name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'middle_name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'last_name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'email',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'phone',
                                            'like',
                                            "%{$search}%"
                                        );
                                }
                            );
                    });
                }
            )
            ->when(
                in_array($status, $statuses, true),
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                array_key_exists($jobType, $jobTypes),
                fn ($query) => $query->where(
                    'job_type',
                    $jobType
                )
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.job-orders.index', [
            'jobOrders' => $jobOrders,
            'search' => $search,
            'status' => $status,
            'jobType' => $jobType,
            'statuses' => $statuses,
            'jobTypes' => $jobTypes,
            'customers' => $this->customersForCreation(),
            'serviceRequests' => $this->serviceRequestsForCreation(),
            'eligibleTechnicians' => $this->eligibleTechnicians(),
        ]);
    }

    /**
     * Display the standalone Job Order creation page.
     *
     * This remains available as a safe direct-link fallback.
     * Normal Admin/Staff workflow uses the modal on the index page.
     */
    public function create(): View
    {
        return view('admin.job-orders.create', [
            'customers' => $this->customersForCreation(),
            'serviceRequests' => $this->serviceRequestsForCreation(),
            'jobTypes' => $this->jobTypes(),
        ]);
    }

    /**
     * Store a newly created pending Job Order.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'customer_id' => [
                    'required',
                    'integer',
                    'exists:customers,id',
                ],
                'service_request_id' => [
                    'nullable',
                    'integer',
                    'exists:service_requests,id',
                ],
                'job_type' => [
                    'required',
                    'string',
                    'in:'.implode(
                        ',',
                        array_keys($this->jobTypes())
                    ),
                ],
                'description' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ],
            [
                'customer_id.required' => 'Please select a subscriber.',
                'customer_id.exists' => 'The selected subscriber could not be found.',

                'service_request_id.exists' => 'The selected service request could not be found.',

                'job_type.required' => 'Please select a Job Order type.',
                'job_type.in' => 'The selected Job Order type is invalid.',

                'description.required' => 'Please provide the work description or instructions.',
                'description.max' => 'The work description cannot exceed 5000 characters.',
            ]
        );

        if (! empty($validated['service_request_id'])) {
            $serviceRequest = ServiceRequest::query()
                ->select([
                    'id',
                    'customer_id',
                ])
                ->findOrFail(
                    $validated['service_request_id']
                );

            if (
                (int) $serviceRequest->customer_id
                !== (int) $validated['customer_id']
            ) {
                throw ValidationException::withMessages([
                    'service_request_id' => 'The selected service request does not belong to the selected subscriber.',
                ]);
            }
        }

        $jobOrder = DB::transaction(
            function () use ($validated) {
                $jobOrder = JobOrder::query()->create([
                    'service_request_id' => $validated['service_request_id'] ?? null,
                    'customer_id' => $validated['customer_id'],
                    'technician_id' => null,
                    'job_order_number' => 'PENDING-'.Str::uuid()->toString(),
                    'job_type' => $validated['job_type'],
                    'description' => trim($validated['description']),
                    'scheduled_date' => null,
                    'scheduled_time' => null,
                    'status' => 'pending',
                ]);

                $jobOrder->update([
                    'job_order_number' => sprintf(
                        'JO-%06d',
                        $jobOrder->id
                    ),
                ]);

                return $jobOrder;
            }
        );

        return redirect()
            ->route('admin.job-orders.index')
            ->with([
                'success' => 'Job Order created successfully.',
                'created_job_order_id' => $jobOrder->id,
            ]);
    }

    /**
     * Update technician assignment and schedule.
     *
     * Pending Job Orders become assigned.
     * Assigned Job Orders may be reassigned or rescheduled.
     */
    public function updateAssignment(
        Request $request,
        JobOrder $jobOrder
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'technician_id' => [
                    'required',
                    'integer',
                ],
                'scheduled_date' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:today',
                ],
                'scheduled_time' => [
                    'required',
                    'date_format:H:i',
                ],
            ],
            [
                'technician_id.required' => 'Please select a technician.',
                'technician_id.integer' => 'The selected technician is invalid.',

                'scheduled_date.required' => 'Please select a scheduled date.',
                'scheduled_date.date_format' => 'The scheduled date is invalid.',
                'scheduled_date.after_or_equal' => 'The scheduled date cannot be in the past.',

                'scheduled_time.required' => 'Please select a scheduled time.',
                'scheduled_time.date_format' => 'The scheduled time is invalid.',
            ]
        );

        $scheduledAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['scheduled_date']
                .' '
                .$validated['scheduled_time']
        );

        if (
            $scheduledAt->lt(
                now()->startOfMinute()
            )
        ) {
            throw ValidationException::withMessages([
                'scheduled_time' => 'The scheduled date and time cannot be in the past.',
            ]);
        }

        $wasInitialAssignment = DB::transaction(
            function () use (
                $jobOrder,
                $validated
            ): bool {
                $lockedJobOrder = JobOrder::query()
                    ->whereKey($jobOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! in_array(
                        $lockedJobOrder->status,
                        [
                            'pending',
                            'assigned',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'assignment' => 'Only pending or assigned Job Orders can be scheduled or reassigned.',
                    ]);
                }

                $technician = Technician::query()
                    ->whereKey(
                        $validated['technician_id']
                    )
                    ->where(
                        'status',
                        'available'
                    )
                    ->whereHas(
                        'user',
                        function ($query) {
                            $query
                                ->where(
                                    'role',
                                    'technician'
                                )
                                ->where(
                                    'account_status',
                                    'active'
                                );
                        }
                    )
                    ->lockForUpdate()
                    ->first();

                if ($technician === null) {
                    throw ValidationException::withMessages([
                        'technician_id' => 'The selected technician is not currently available for assignment.',
                    ]);
                }

                $wasPending =
                    $lockedJobOrder->status === 'pending';

                $lockedJobOrder->update([
                    'technician_id' => $technician->id,
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time' => $validated['scheduled_time'],
                    'status' => 'assigned',
                ]);

                return $wasPending;
            }
        );

        return back()->with(
            'success',
            $wasInitialAssignment
                ? 'Job Order assigned and scheduled successfully.'
                : 'Job Order assignment and schedule updated successfully.'
        );
    }

    /**
     * Display one Job Order.
     *
     * This remains available as a safe direct-link fallback.
     * Normal Admin/Staff workflow uses the details modal.
     */
    public function show(JobOrder $jobOrder): View
    {
        $jobOrder->load([
            'customer',
            'serviceRequest.servicePlan',
            'technician.user',
            'notes.user',
            'proofs.uploader',
        ]);

        return view('admin.job-orders.show', [
            'jobOrder' => $jobOrder,
            'jobTypes' => $this->jobTypes(),
            'eligibleTechnicians' => $this->eligibleTechnicians(),
        ]);
    }

    /**
     * Return subscribers available to the Create Job Order workflow.
     */
    private function customersForCreation()
    {
        return Customer::query()
            ->select([
                'id',
                'customer_code',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'phone',
                'status',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Return Service Requests available to the Create Job Order workflow.
     */
    private function serviceRequestsForCreation()
    {
        return ServiceRequest::query()
            ->select([
                'id',
                'ticket_number',
                'customer_id',
                'request_type',
                'status',
                'requested_date',
            ])
            ->latest('id')
            ->get();
    }

    /**
     * Return technicians eligible for Job Order assignment.
     *
     * A technician must be operationally available and linked
     * to an active Technician user account.
     */
    private function eligibleTechnicians()
    {
        return Technician::query()
            ->select([
                'id',
                'user_id',
                'technician_code',
                'specialization',
                'status',
            ])
            ->with([
                'user:id,name,role,account_status',
            ])
            ->where(
                'status',
                'available'
            )
            ->whereHas(
                'user',
                function ($query) {
                    $query
                        ->where(
                            'role',
                            'technician'
                        )
                        ->where(
                            'account_status',
                            'active'
                        );
                }
            )
            ->orderBy('technician_code')
            ->get();
    }

    /**
     * Return the FRS-approved Job Order types.
     */
    private function jobTypes(): array
    {
        return [
            'new_installation' => 'New Installation',
            'site_survey' => 'Site Survey',
            'repair' => 'Repair',
            'line_maintenance' => 'Line Maintenance',
            'physical_disconnection' => 'Physical Disconnection',
        ];
    }
}
