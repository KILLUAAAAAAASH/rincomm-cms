<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\TicketCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    private const TECHNICAL_ISSUES = [
        'no_internet',
        'slow_connection',
        'intermittent_connection',
        'other',
    ];

    private const NONTECHNICAL_ISSUES = [
        'billing',
        'account',
        'other',
    ];

    public function index(Request $request): View
    {
        $customer = $request->user()->customer;

        $tickets = $customer
            ? $customer->serviceRequests()
                ->with(['servicePlan', 'subscription'])
                ->latest('id')
                ->paginate(15)
            : ServiceRequest::query()
                ->whereRaw('1 = 0')
                ->paginate(15);

        return view('customer.tickets.index', [
            'customer' => $customer,
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): View
    {
        $customer = $request->user()->customer;

        $subscriptions = $customer
            ? $customer->subscriptions()
                ->with('servicePlan')
                ->where('status', 'active')
                ->latest('id')
                ->get()
            : collect();

        return view('customer.tickets.create', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function store(
        Request $request,
        TicketCreationService $ticketCreation
    ): RedirectResponse {
        $customer = $request->user()->customer;

        if (! $customer) {
            return redirect()
                ->route('customer.application.create')
                ->with(
                    'error',
                    'A subscriber profile is required before submitting a ticket.'
                );
        }

        $validated = $request->validate([
            'request_type' => [
                'required',
                Rule::in(['technical', 'non_technical']),
            ],
            'issue_type' => [
                'required',
                'string',
                'max:50',
            ],
            'subscription_id' => [
                'nullable',
                'integer',
                'exists:subscriptions,id',
            ],
            'subject' => [
                'required',
                'string',
                'max:160',
            ],
            'description' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $validIssues = $validated['request_type'] === 'technical'
            ? self::TECHNICAL_ISSUES
            : self::NONTECHNICAL_ISSUES;

        if (! in_array(
            $validated['issue_type'],
            $validIssues,
            true
        )) {
            throw ValidationException::withMessages([
                'issue_type' =>
                    'Select an issue that matches the ticket category.',
            ]);
        }

        $ticket = $ticketCreation->create(
            $customer,
            $validated,
            false
        );

        return redirect()
            ->route('customer.tickets.show', $ticket->id)
            ->with(
                'success',
                'Your support ticket was submitted successfully.'
            );
    }

    public function show(
        Request $request,
        int $ticket
    ): View {
        $customer = $request->user()->customer;

        abort_if($customer === null, 404);

        $serviceRequest = $customer->serviceRequests()
            ->with([
                'servicePlan',
                'subscription',
                'ticketMessages' => fn ($query) => $query
                    ->with('user:id,name,role')
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->findOrFail($ticket);

        return view('customer.tickets.show', [
            'ticket' => $serviceRequest,
        ]);
    }
}