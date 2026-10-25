<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\TicketConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketConversationController extends Controller
{
    public function reply(
        Request $request,
        int $ticket,
        TicketConversationService $conversation
    ): RedirectResponse {
        $customer = $request->user()->customer;

        abort_if($customer === null, 404);

        // Scope the ticket lookup to the signed-in subscriber.
        // Other subscribers' ticket IDs must not be accessible.
        $serviceRequest = $customer->serviceRequests()
            ->findOrFail($ticket);

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        // The service independently checks ownership, account
        // status, message content, and closed-ticket restrictions.
        $conversation->reply(
            $serviceRequest,
            $request->user(),
            $validated['message']
        );

        return redirect()
            ->route(
                'customer.tickets.show',
                $serviceRequest->id
            )
            ->with(
                'success',
                'Your support ticket reply was sent successfully.'
            );
    }
}