<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    private const NEXT_STATUSES = [
        'assigned' => 'in_progress',
        'in_progress' => 'resolved',
        'resolved' => 'closed',
    ];

    public function assign(
        ServiceRequest $ticket,
        User $assignee,
        User $actor
    ): ServiceRequest {
        $this->authorizeStaff($actor);

        return DB::transaction(function () use (
            $ticket,
            $assignee
        ): ServiceRequest {
            $lockedTicket = ServiceRequest::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $lockedTicket->status,
                ['open', 'assigned'],
                true
            )) {
                throw ValidationException::withMessages([
                    'assigned_user_id' =>
                        'Only open or assigned tickets can be assigned.',
                ]);
            }

            $eligible = User::query()
                ->whereKey($assignee->getKey())
                ->where('account_status', 'active')
                ->whereIn('role', ['admin', 'staff'])
                ->exists();

            if (! $eligible) {
                throw ValidationException::withMessages([
                    'assigned_user_id' =>
                        'Select an active administrator or staff member.',
                ]);
            }

            $statusChanged = $lockedTicket->status === 'open';

            $lockedTicket->update([
                'assigned_user_id' => $assignee->getKey(),
                'assigned_at' => now(),
                'status' => 'assigned',
            ]);

            $lockedTicket = $lockedTicket->refresh();

            if ($statusChanged) {
                app(TicketNotificationService::class)
                    ->ticketStatusChanged($lockedTicket);
            }

            return $lockedTicket;
        });
    }

    public function transition(
        ServiceRequest $ticket,
        string $nextStatus,
        User $actor
    ): ServiceRequest {
        $this->authorizeStaff($actor);

        return DB::transaction(function () use (
            $ticket,
            $nextStatus,
            $actor
        ): ServiceRequest {
            $lockedTicket = ServiceRequest::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->assigned_user_id === null) {
                throw ValidationException::withMessages([
                    'status' => 'Assign the ticket before advancing it.',
                ]);
            }

            if (
                $actor->role === 'staff' &&
                (int) $lockedTicket->assigned_user_id !==
                    (int) $actor->getKey()
            ) {
                throw new AuthorizationException(
                    'Only the assigned staff member may advance this ticket.'
                );
            }

            $expected = self::NEXT_STATUSES[$lockedTicket->status]
                ?? null;

            if ($expected !== $nextStatus) {
                throw ValidationException::withMessages([
                    'status' => 'This ticket status transition is not allowed.',
                ]);
            }

            $lockedTicket->update([
                'status' => $nextStatus,
            ]);

            $lockedTicket = $lockedTicket->refresh();

            app(TicketNotificationService::class)
                ->ticketStatusChanged($lockedTicket);

            return $lockedTicket;
        });
    }

    private function authorizeStaff(User $actor): void
    {
        if (
            ! in_array($actor->role, ['admin', 'staff'], true) ||
            $actor->account_status !== 'active'
        ) {
            throw new AuthorizationException(
                'You are not authorized to manage support tickets.'
            );
        }
    }
}