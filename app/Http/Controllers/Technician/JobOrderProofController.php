<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use App\Models\JobOrderProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class JobOrderProofController extends Controller
{
    /**
     * Store a private proof-of-work file for an assigned Job Order.
     */
    public function store(
        Request $request,
        JobOrder $jobOrder
    ): RedirectResponse {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
        );

        abort_unless(
            $jobOrder->status === 'in_progress',
            422,
            'Proof of work can only be uploaded while the job is in progress.'
        );

        $validated = $request->validate(
            [
                'proof' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'max:5120',
                ],
                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ],
            [
                'proof.required' => 'Please select a proof-of-work file to upload.',
                'proof.file' => 'The selected upload must be a valid file.',
                'proof.mimes' => 'Only PDF, JPG, JPEG, and PNG files are allowed.',
                'proof.max' => 'The proof-of-work file cannot exceed 5 MB.',
                'notes.max' => 'Proof notes cannot exceed 1000 characters.',
            ]
        );

        $file = $validated['proof'];

        $filePath = $file->store(
            "job-order-proofs/{$jobOrder->id}",
            'local'
        );

        try {
            $jobOrder->proofs()->create([
                'uploaded_by' => $request->user()->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'notes' => isset($validated['notes'])
                    ? trim($validated['notes'])
                    : null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($filePath);

            throw $exception;
        }

        return back()->with(
            'success',
            'Proof of work uploaded successfully.'
        );
    }

    /**
     * Display a private proof-of-work file in the browser.
     */
    public function show(
        Request $request,
        JobOrder $jobOrder,
        JobOrderProof $proof
    ): StreamedResponse {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
        );

        $this->ensureProofBelongsToJobOrder(
            $jobOrder,
            $proof
        );

        abort_unless(
            Storage::disk('local')->exists($proof->file_path),
            404
        );

        return Storage::disk('local')->response(
            $proof->file_path,
            $proof->original_name,
            [
                'Content-Type' => $proof->mime_type
                    ?: 'application/octet-stream',
                'Content-Disposition' =>
                'inline; filename="' .
                    addslashes($proof->original_name) .
                    '"',
            ]
        );
    }

    /**
     * Download a private proof-of-work file.
     */
    public function download(
        Request $request,
        JobOrder $jobOrder,
        JobOrderProof $proof
    ): StreamedResponse {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
        );

        $this->ensureProofBelongsToJobOrder(
            $jobOrder,
            $proof
        );

        abort_unless(
            Storage::disk('local')->exists($proof->file_path),
            404
        );

        return Storage::disk('local')->download(
            $proof->file_path,
            $proof->original_name
        );
    }

    /**
     * Delete a proof-of-work file while the Job Order is still in progress.
     */
    public function destroy(
        Request $request,
        JobOrder $jobOrder,
        JobOrderProof $proof
    ): RedirectResponse {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
        );

        $this->ensureProofBelongsToJobOrder(
            $jobOrder,
            $proof
        );

        abort_unless(
            $jobOrder->status === 'in_progress',
            422,
            'Proof of work cannot be deleted after the job is no longer in progress.'
        );

        $filePath = $proof->file_path;

        $proof->delete();

        Storage::disk('local')->delete($filePath);

        return back()->with(
            'success',
            'Proof of work deleted successfully.'
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
                && $jobOrder->technician_id === $technician->id,
            404
        );
    }

    /**
     * Prevent access to a proof that belongs to another Job Order.
     */
    private function ensureProofBelongsToJobOrder(
        JobOrder $jobOrder,
        JobOrderProof $proof
    ): void {
        abort_unless(
            $proof->job_order_id === $jobOrder->id,
            404
        );
    }
}
