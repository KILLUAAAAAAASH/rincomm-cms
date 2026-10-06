<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use App\Models\JobOrderProof;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class JobOrderProofController extends Controller
{
    /**
     * Store a private proof-of-work file for an in-progress Job Order.
     */
    public function store(
        Request $request,
        JobOrder $jobOrder
    ): RedirectResponse {
        $this->ensureJobOrderBelongsToTechnician(
            $request,
            $jobOrder
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

        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        $file = $validated['proof'];

        $notes = isset($validated['notes'])
            && trim($validated['notes']) !== ''
                ? trim($validated['notes'])
                : null;

        $filePath = null;

        try {
            DB::transaction(function () use (
                $request,
                $jobOrder,
                $technician,
                $file,
                $notes,
                &$filePath
            ): void {
                $lockedJobOrder = JobOrder::query()
                    ->whereKey($jobOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    $lockedJobOrder->technician_id === $technician->id,
                    404
                );

                if ($lockedJobOrder->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'proof' => 'Proof of work can only be uploaded while the job is in progress.',
                    ]);
                }

                $storedPath = $file->store(
                    "job-order-proofs/{$lockedJobOrder->id}",
                    'local'
                );

                if (
                    ! is_string($storedPath)
                    || $storedPath === ''
                ) {
                    throw ValidationException::withMessages([
                        'proof' => 'The proof-of-work file could not be stored. Please try again.',
                    ]);
                }

                $filePath = $storedPath;

                $lockedJobOrder->proofs()->create([
                    'uploaded_by' => $request->user()->id,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $storedPath,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'notes' => $notes,
                ]);
            });
        } catch (Throwable $exception) {
            if (
                is_string($filePath)
                && $filePath !== ''
            ) {
                $this->localDisk()->delete($filePath);
            }

            throw $exception;
        }

        return back()
            ->with(
                'success',
                'Proof of work uploaded successfully.'
            )
            ->with(
                'proof_job_order_id',
                (string) $jobOrder->id
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

        $disk = $this->localDisk();

        abort_unless(
            $disk->exists($proof->file_path),
            404
        );

        return $disk->response(
            $proof->file_path,
            $proof->original_name,
            [
                'Content-Type' => $proof->mime_type
                    ?: 'application/octet-stream',
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

        $disk = $this->localDisk();

        abort_unless(
            $disk->exists($proof->file_path),
            404
        );

        return $disk->download(
            $proof->file_path,
            $proof->original_name
        );
    }

    /**
     * Delete a proof-of-work file while the Job Order is in progress.
     */
    public function destroy(
        Request $request,
        JobOrder $jobOrder,
        JobOrderProof $proof
    ): RedirectResponse {
        $technician = $request->user()->technician;

        abort_unless($technician !== null, 404);

        $filePath = DB::transaction(function () use (
            $jobOrder,
            $proof,
            $technician
        ): string {
            $lockedJobOrder = JobOrder::query()
                ->whereKey($jobOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedJobOrder->technician_id === $technician->id,
                404
            );

            if ($lockedJobOrder->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'proof' => 'Proof of work can only be deleted while the job is in progress.',
                ]);
            }

            $lockedProof = JobOrderProof::query()
                ->whereKey($proof->id)
                ->where(
                    'job_order_id',
                    $lockedJobOrder->id
                )
                ->lockForUpdate()
                ->firstOrFail();

            $storedPath = $lockedProof->file_path;

            $lockedProof->delete();

            return $storedPath;
        });

        $this->localDisk()->delete($filePath);

        return back()
            ->with(
                'success',
                'Proof of work deleted successfully.'
            )
            ->with(
                'proof_job_order_id',
                (string) $jobOrder->id
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
     * Prevent access to a proof belonging to another Job Order.
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

    /**
     * Return the private local filesystem with its concrete adapter type.
     */
    private function localDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk;
    }
}
