<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;
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
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $requestType = (string) $request->query('request_type', '');
        $sort = (string) $request->query('sort', 'newest');

        $statuses = [
            'open',
            'assigned',
            'in_progress',
            'resolved',
            'closed',
        ];

        $allowedSorts = [
            'newest',
            'oldest',
            'priority_high',
            'priority_low',
            'ticket_asc',
            'ticket_desc',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }

        $query = ServiceRequest::query()
            ->with(['customer', 'servicePlan', 'subscription'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('customer_code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                in_array($status, $statuses, true),
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                in_array($requestType, ['technical', 'non_technical'], true),
                fn ($query) => $query->where('request_type', $requestType)
            );

        switch ($sort) {
            case 'oldest':
                $query->orderBy('id');
                break;

            case 'priority_high':
                $query->orderByRaw("
                    CASE priority
                        WHEN 'urgent' THEN 4
                        WHEN 'high' THEN 3
                        WHEN 'normal' THEN 2
                        WHEN 'low' THEN 1
                        ELSE 0
                    END DESC
                ")->orderByDesc('id');
                break;

            case 'priority_low':
                $query->orderByRaw("
                    CASE priority
                        WHEN 'low' THEN 1
                        WHEN 'normal' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'urgent' THEN 4
                        ELSE 5
                    END ASC
                ")->orderByDesc('id');
                break;

            case 'ticket_asc':
                $query->orderBy('ticket_number')
                    ->orderBy('id');
                break;

            case 'ticket_desc':
                $query->orderByDesc('ticket_number')
                    ->orderByDesc('id');
                break;

            default:
                $query->orderByDesc('id');
                break;
        }

        $tickets = $query
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'status' => $status,
            'requestType' => $requestType,
            'sort' => $sort,
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->select([
                'id',
                'customer_code',
                'first_name',
                'middle_name',
                'last_name',
                'status',
            ])
            ->with([
                'subscriptions' => function ($query) {
                    $query
                        ->where('status', 'active')
                        ->with('servicePlan')
                        ->latest('id');
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('admin.tickets.create', [
            'customers' => $customers,
        ]);
    }

    public function store(
        Request $request,
        TicketCreationService $ticketCreation
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],
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
            'priority' => [
                'sometimes',
                'required',
                Rule::in([
                    'low',
                    'normal',
                    'high',
                    'urgent',
                ]),
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

        $customer = Customer::query()
            ->findOrFail($validated['customer_id']);

        $ticket = $ticketCreation->create(
            $customer,
            $validated,
            true
        );

        return redirect()
            ->route('admin.tickets.show', $ticket->id)
            ->with(
                'success',
                'Support ticket created successfully.'
            );
    }

    public function show(int $ticket): View
    {
        $serviceRequest = ServiceRequest::query()
            ->with([
                'customer',
                'subscription.servicePlan',
                'servicePlan',
                'assignedUser',
                'jobOrders',
                'ticketMessages' => fn ($query) => $query
                    ->with('user:id,name,role')
                    ->orderBy('created_at')
                    ->orderBy('id'),
                'ticketNotes' => fn ($query) => $query
                    ->with('user:id,name,role')
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->findOrFail($ticket);

        $assignableUsers = User::query()
            ->select(['id', 'name', 'role'])
            ->where('account_status', 'active')
            ->whereIn('role', ['admin', 'staff'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('admin.tickets.show', [
            'ticket' => $serviceRequest,
            'assignableUsers' => $assignableUsers,
        ]);
    }
}