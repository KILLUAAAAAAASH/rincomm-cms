<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanChangeStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerName,
        public readonly string $status,
        public readonly string $oldPlanName,
        public readonly string $newPlanName,
        public readonly ?string $effectiveBillingDate = null,
        public readonly ?string $reviewNotes = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rincomm plan change ' . $this->status
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.plan-change-status');
    }

    public function attachments(): array
    {
        return [];
    }
}
