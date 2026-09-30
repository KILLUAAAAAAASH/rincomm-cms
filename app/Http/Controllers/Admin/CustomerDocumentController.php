<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerDocumentController extends Controller
{
    /**
     * Store a private subscriber document.
     */
    public function store(
        Request $request,
        Customer $subscriber
    ): RedirectResponse {
        $validated = $request->validate(
            [
                'document_type' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'document' => [
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
                'document_type.required' => 'Document type is required.',
                'document_type.max' => 'Document type cannot exceed 100 characters.',

                'document.required' => 'Please select a document to upload.',
                'document.file' => 'The selected upload must be a valid file.',
                'document.mimes' => 'Only PDF, JPG, JPEG, and PNG files are allowed.',
                'document.max' => 'The document cannot exceed 5 MB.',

                'notes.max' => 'Document notes cannot exceed 1000 characters.',
            ]
        );

        $file = $validated['document'];

        $filePath = $file->store(
            "customer-documents/{$subscriber->id}",
            'local'
        );

        try {
            $subscriber->documents()->create([
                'uploaded_by' => $request->user()->id,
                'document_type' => trim($validated['document_type']),
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

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with(
                'success',
                'Customer document uploaded successfully.'
            );
    }

    /**
     * Display a private subscriber document in the browser.
     */
    public function show(
        Customer $subscriber,
        CustomerDocument $document
    ): StreamedResponse {
        $this->ensureDocumentBelongsToSubscriber(
            $subscriber,
            $document
        );

        abort_unless(
            Storage::disk('local')->exists($document->file_path),
            404
        );

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type
                    ?: 'application/octet-stream',
                'Content-Disposition' =>
                'inline; filename="' .
                    addslashes($document->original_name) .
                    '"',
            ]
        );
    }

    /**
     * Download a private subscriber document.
     */
    public function download(
        Customer $subscriber,
        CustomerDocument $document
    ): StreamedResponse {
        $this->ensureDocumentBelongsToSubscriber(
            $subscriber,
            $document
        );

        abort_unless(
            Storage::disk('local')->exists($document->file_path),
            404
        );

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Delete a subscriber document and its private file.
     */
    public function destroy(
        Customer $subscriber,
        CustomerDocument $document
    ): RedirectResponse {
        $this->ensureDocumentBelongsToSubscriber(
            $subscriber,
            $document
        );

        $filePath = $document->file_path;

        $document->delete();

        Storage::disk('local')->delete($filePath);

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with(
                'success',
                'Customer document deleted successfully.'
            );
    }

    /**
     * Prevent access to a document that belongs to another subscriber.
     */
    private function ensureDocumentBelongsToSubscriber(
        Customer $subscriber,
        CustomerDocument $document
    ): void {
        abort_unless(
            $document->customer_id === $subscriber->id,
            404
        );
    }
}
