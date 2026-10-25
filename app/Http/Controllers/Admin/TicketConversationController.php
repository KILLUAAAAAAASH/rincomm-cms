<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\TicketConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketConversationController extends Controller
{
    public function reply(
        Request $request,
        ServiceRequest $ticket,
        TicketConversationService $conversation
    ): RedirectResponse {
        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $conversation->reply(
            $ticket,
            $request->user(),
            $validated['message']
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with(
                'success',
                'Support ticket reply posted successfully.'
            );
    }

    public function addNote(
        Request $request,
        ServiceRequest $ticket,
        TicketConversationService $conversation
    ): RedirectResponse {
        $validated = $request->validate([
            'note' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $conversation->addInternalNote(
            $ticket,
            $request->user(),
            $validated['note']
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with(
                'success',
                'Internal ticket note saved successfully.'
            );
    }
}