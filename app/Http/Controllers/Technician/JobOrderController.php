<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobOrderController extends Controller
{
    /**
     * Display Job Orders assigned to the authenticated technician.
     */
    public function index(Request $request): View
    {
        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        $allowedStatuses = [
            'assigned',
            'in_progress',
            'completed',
            'cancelled',
        ];

        $status = $request->string('status')->toString();

        if (
            $status !== ''
            && ! in_array($status, $allowedStatuses, true)
        ) {
            abort(404);
        }

        $jobOrders = JobOrder::query()
            ->with([
                'customer',
                'serviceRequest.servicePlan',
                'notes.user',
                'proofs' => fn ($query) => $query
                    ->latest(),
                'proofs.uploader',
            ])
            ->where(
                'technician_id',
                $technician->id
            )
            ->when(
                $status !== '',
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->orderByRaw('scheduled_date IS NULL')
            ->orderBy('scheduled_date')
            ->orderByRaw('scheduled_time IS NULL')
            ->orderBy('scheduled_time')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('technician.job-orders.index', [
            'technician' => $technician,
            'jobOrders' => $jobOrders,
            'status' => $status,
        ]);
    }

    /**
     * Display one Job Order assigned to the authenticated technician.
     *
     * This route remains available as a secure fallback/direct link.
     * The normal technician workflow uses the Job Orders workspace modal.
     */
    public function show(
        Request $request,
        JobOrder $jobOrder
    ): View {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
        );

        $jobOrder->load([
            'customer',
            'serviceRequest.servicePlan',
            'notes.user',
            'proofs' => fn ($query) => $query
                ->latest(),
            'proofs.uploader',
        ]);

        return view('technician.job-orders.show', [
            'jobOrder' => $jobOrder,
        ]);
    }

    /**
     * Start an assigned Job Order.
     */
    public function start(
        Request $request,
        JobOrder $jobOrder
    ): RedirectResponse {
        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        DB::transaction(function () use (
            $jobOrder,
            $technician
        ): void {
            $lockedJobOrder = JobOrder::query()
                ->whereKey($jobOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedJobOrder->technician_id
                    === $technician->id,
                404
            );

            if ($lockedJobOrder->status !== 'assigned') {
                throw ValidationException::withMessages([
                    'job_order' => 'Only assigned Job Orders can be started.',
                ]);
            }

            if ($lockedJobOrder->job_type === null) {
                throw ValidationException::withMessages([
                    'job_order' => 'This Job Order does not have a job type yet. Please contact the administrator or staff.',
                ]);
            }

            $lockedJobOrder->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        });

        return redirect()
            ->route('technician.job-orders.index')
            ->with(
                'success',
                'Job Order started successfully.'
            );
    }

    /**
     * Submit the completion report for an in-progress Job Order.
     */
    public function complete(
        Request $request,
        JobOrder $jobOrder
    ): RedirectResponse {
        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        abort_unless(
            $jobOrder->technician_id === $technician->id,
            404
        );

        $validated = $request->validate(
            [
                'completion_report' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ],
            [
                'completion_report.required' => 'A completion report is required.',
                'completion_report.max' => 'The completion report cannot exceed 5000 characters.',
            ]
        );

        DB::transaction(function () use (
            $jobOrder,
            $technician,
            $validated
        ): void {
            $lockedJobOrder = JobOrder::query()
                ->whereKey($jobOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedJobOrder->technician_id
                    === $technician->id,
                404
            );

            if ($lockedJobOrder->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'completion_report' => 'Only an in-progress Job Order can be completed.',
                ]);
            }

            if ($lockedJobOrder->job_type === null) {
                throw ValidationException::withMessages([
                    'completion_report' => 'This Job Order does not have a job type yet. Please contact the administrator or staff.',
                ]);
            }

            if (! $lockedJobOrder->proofs()->exists()) {
                throw ValidationException::withMessages([
                    'completion_report' => 'Upload at least one proof-of-work file before completing this Job Order.',
                ]);
            }

            $lockedJobOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completion_report' => trim(
                    $validated['completion_report']
                ),
            ]);
        });

        return redirect()
            ->route('technician.job-orders.index')
            ->with(
                'success',
                'Job Order completed successfully.'
            );
    }

    /**
     * Ensure the authenticated technician owns the Job Order.
     */
    private function ensureJobOrderBelongsToTechnician(
        Request $request,
        JobOrder $jobOrder
    ): void {
        $technician = $request->user()->technician;

        abort_unless(
            $technician !== null
                && $jobOrder->technician_id
                    === $technician->id,
            404
        );
    }
}
