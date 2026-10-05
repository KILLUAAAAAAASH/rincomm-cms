<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobOrderController extends Controller
{
    /**
     * Display the operational Job Order list.
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
                'technician',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('job_order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query
                                ->where('customer_code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                in_array($status, $statuses, true),
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                array_key_exists($jobType, $jobTypes),
                fn ($query) => $query->where('job_type', $jobType)
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
        ]);
    }

    /**
     * Display the Job Order creation form.
     */
    public function create(): View
    {
        $customers = Customer::query()
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

        $serviceRequests = ServiceRequest::query()
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

        return view('admin.job-orders.create', [
            'customers' => $customers,
            'serviceRequests' => $serviceRequests,
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
                    'in:' . implode(',', array_keys($this->jobTypes())),
                ],
                'description' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ],
            [
                'customer_id.required' =>
                    'Please select a subscriber.',
                'customer_id.exists' =>
                    'The selected subscriber could not be found.',

                'service_request_id.exists' =>
                    'The selected service request could not be found.',

                'job_type.required' =>
                    'Please select a Job Order type.',
                'job_type.in' =>
                    'The selected Job Order type is invalid.',

                'description.required' =>
                    'Please provide the work description or instructions.',
                'description.max' =>
                    'The work description cannot exceed 5000 characters.',
            ]
        );

        if (! empty($validated['service_request_id'])) {
            $serviceRequest = ServiceRequest::query()
                ->select([
                    'id',
                    'customer_id',
                ])
                ->findOrFail($validated['service_request_id']);

            if (
                (int) $serviceRequest->customer_id
                !== (int) $validated['customer_id']
            ) {
                throw ValidationException::withMessages([
                    'service_request_id' =>
                        'The selected service request does not belong to the selected subscriber.',
                ]);
            }
        }

        $jobOrder = DB::transaction(function () use ($validated) {
            $jobOrder = JobOrder::query()->create([
                'service_request_id' =>
                    $validated['service_request_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'technician_id' => null,
                'job_order_number' =>
                    'PENDING-' . Str::uuid()->toString(),
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
        });

        return redirect()
            ->route('admin.job-orders.show', $jobOrder)
            ->with(
                'success',
                'Job Order created successfully.'
            );
    }

    /**
     * Display one Job Order.
     */
    public function show(JobOrder $jobOrder): View
    {
        $jobOrder->load([
            'customer',
            'serviceRequest.servicePlan',
            'technician',
            'notes.user',
            'proofs.uploader',
        ]);

        return view('admin.job-orders.show', [
            'jobOrder' => $jobOrder,
            'jobTypes' => $this->jobTypes(),
        ]);
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
