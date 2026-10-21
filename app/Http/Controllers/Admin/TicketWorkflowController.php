<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketWorkflowController extends Controller
{
    public function assign(
        Request $request,
        ServiceRequest $ticket,
        TicketWorkflowService $workflow
    ): RedirectResponse {
        $validated = $request->validate([
            'assigned_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        $assignee = User::query()->findOrFail(
            $validated['assigned_user_id']
        );

        $workflow->assign(
            $ticket,
            $assignee,
            $request->user()
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with(
                'success',
                'Support ticket assigned successfully.'
            );
    }

    public function transition(
        Request $request,
        ServiceRequest $ticket,
        TicketWorkflowService $workflow
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'in_progress',
                    'resolved',
                    'closed',
                ]),
            ],
        ]);

        $workflow->transition(
            $ticket,
            $validated['status'],
            $request->user()
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with(
                'success',
                'Support ticket status updated successfully.'
            );
    }
}