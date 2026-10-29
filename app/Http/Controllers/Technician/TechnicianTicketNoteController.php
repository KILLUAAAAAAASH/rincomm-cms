<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use App\Services\TicketConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TechnicianTicketNoteController extends Controller
{
    public function store(
        Request $request,
        JobOrder $jobOrder,
        TicketConversationService $conversation
    ): RedirectResponse {
        $technician = $request->user()->technician;

        abort_unless(
            $technician !== null
                && $jobOrder->technician_id === $technician->id
                && $jobOrder->service_request_id !== null
                && in_array($jobOrder->status, ['assigned', 'in_progress'], true),
            404
        );

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $conversation->addTechnicianInternalNote(
            $jobOrder,
            $request->user(),
            $validated['note']
        );

        return redirect()
            ->route('technician.job-orders.show', $jobOrder)
            ->with('success', 'Internal ticket note saved successfully.');
    }
}
