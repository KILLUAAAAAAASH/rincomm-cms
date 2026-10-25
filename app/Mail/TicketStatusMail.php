<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $ticketNumber,
        public readonly string $ticketSubject,
        public readonly string $status,
        public readonly bool $created = false
    ) {}

    public function envelope(): Envelope
    {
        $description = $this->created
            ? 'created'
            : 'status updated';

        return new Envelope(
            subject: "Rincomm ticket {$this->ticketNumber} {$description}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-status'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}