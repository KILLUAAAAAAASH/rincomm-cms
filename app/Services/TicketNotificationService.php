<?php

namespace App\Services;

use App\Mail\TicketStatusMail;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TicketNotificationService
{
    public function ticketCreated(ServiceRequest $ticket): void
    {
        $this->scheduleEmail($ticket, true);
    }

    public function ticketStatusChanged(ServiceRequest $ticket): void
    {
        $this->scheduleEmail($ticket, false);
    }

    private function scheduleEmail(
        ServiceRequest $ticket,
        bool $created
    ): void {
        $ticket->loadMissing('customer.user');

        $customer = $ticket->customer;

        if ($customer === null) {
            Log::warning('Ticket notification skipped: subscriber missing.', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $email = trim((string) (
            $customer->email ?: $customer->user?->email
        ));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Ticket notification skipped: invalid subscriber email.', [
                'ticket_id' => $ticket->id,
                'customer_id' => $customer->id,
            ]);

            return;
        }

        $customerName = trim(
            $customer->first_name . ' ' . $customer->last_name
        );

        $ticketId = $ticket->id;
        $ticketNumber = (string) $ticket->ticket_number;
        $ticketSubject = (string) $ticket->subject;
        $status = (string) $ticket->status;

        DB::afterCommit(function () use (
            $email,
            $customerName,
            $ticketId,
            $ticketNumber,
            $ticketSubject,
            $status,
            $created
        ): void {
            try {
                Mail::to($email)->send(new TicketStatusMail(
                    customerName: $customerName,
                    ticketNumber: $ticketNumber,
                    ticketSubject: $ticketSubject,
                    status: $status,
                    created: $created
                ));
            } catch (Throwable $exception) {
                Log::warning('Ticket email notification failed.', [
                    'ticket_id' => $ticketId,
                    'notification_type' => $created
                        ? 'created'
                        : 'status_updated',
                    'error_type' => get_class($exception),
                ]);
            }
        });
    }
}