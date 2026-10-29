<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\ServiceRequest;
use App\Models\TicketMessage;
use App\Models\TicketNote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketConversationService
{
    public function reply(
        ServiceRequest $ticket,
        User $actor,
        string $message
    ): TicketMessage {
        $message = $this->validateContent($message, 'message');

        return DB::transaction(function () use (
            $ticket,
            $actor,
            $message
        ): TicketMessage {
            $lockedTicket = ServiceRequest::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeParticipant($lockedTicket, $actor);
            $this->ensureTicketIsOpenForCommunication($lockedTicket);

            return $lockedTicket->ticketMessages()->create([
                'user_id' => $actor->getKey(),
                'message' => $message,
            ]);
        });
    }

    public function addInternalNote(
        ServiceRequest $ticket,
        User $actor,
        string $note
    ): TicketNote {
        $note = $this->validateContent($note, 'note');

        return DB::transaction(function () use (
            $ticket,
            $actor,
            $note
        ): TicketNote {
            $lockedTicket = ServiceRequest::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorizeSupportEmployee($actor);
            $this->ensureTicketIsOpenForCommunication($lockedTicket);

            return $lockedTicket->ticketNotes()->create([
                'user_id' => $actor->getKey(),
                'note' => $note,
            ]);
        });
    }

    /**
     * Add a shared internal note from an actively assigned technician.
     * Revalidate assignment inside the transaction before writing.
     */
    public function addTechnicianInternalNote(
        JobOrder $jobOrder,
        User $actor,
        string $note
    ): TicketNote {
        $note = $this->validateContent($note, 'note');

        return DB::transaction(function () use ($jobOrder, $actor, $note): TicketNote {
            $lockedJobOrder = JobOrder::query()
                ->whereKey($jobOrder->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $technician = $actor->role === 'technician'
                && $actor->account_status === 'active'
                ? $actor->technician
                : null;

            abort_unless(
                $technician !== null
                    && $lockedJobOrder->technician_id === $technician->getKey()
                    && $lockedJobOrder->service_request_id !== null
                    && in_array($lockedJobOrder->status, ['assigned', 'in_progress'], true),
                404
            );

            $ticket = ServiceRequest::query()
                ->whereKey($lockedJobOrder->service_request_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureTicketIsOpenForCommunication($ticket);

            return $ticket->ticketNotes()->create([
                'user_id' => $actor->getKey(),
                'note' => $note,
            ]);
        });
    }

    private function authorizeParticipant(
        ServiceRequest $ticket,
        User $actor
    ): void {
        if ($actor->account_status !== 'active') {
            throw new AuthorizationException(
                'Your account cannot participate in this ticket.'
            );
        }

        if (in_array($actor->role, ['admin', 'staff'], true)) {
            return;
        }

        if ($actor->role !== 'customer') {
            throw new AuthorizationException(
                'You are not authorized to reply to this ticket.'
            );
        }

        $ownsTicket = Customer::query()
            ->whereKey($ticket->customer_id)
            ->where('user_id', $actor->getKey())
            ->exists();

        if (! $ownsTicket) {
            throw new AuthorizationException(
                'You are not authorized to reply to this ticket.'
            );
        }
    }

    private function authorizeSupportEmployee(User $actor): void
    {
        if (
            $actor->account_status !== 'active'
            || ! in_array(
                $actor->role,
                ['admin', 'staff'],
                true
            )
        ) {
            throw new AuthorizationException(
                'Only active administrators and staff can add internal notes.'
            );
        }
    }

    private function ensureTicketIsOpenForCommunication(
        ServiceRequest $ticket
    ): void {
        if ($ticket->status === 'closed') {
            throw ValidationException::withMessages([
                'ticket' => 'Closed tickets are read-only.',
            ]);
        }
    }

    private function validateContent(
        string $value,
        string $field
    ): string {
        $value = trim($value);

        if ($value === '') {
            throw ValidationException::withMessages([
                $field => 'Please enter a message.',
            ]);
        }

        if (mb_strlen($value) > 5000) {
            throw ValidationException::withMessages([
                $field => 'The text must not exceed 5000 characters.',
            ]);
        }

        return $value;
    }
}