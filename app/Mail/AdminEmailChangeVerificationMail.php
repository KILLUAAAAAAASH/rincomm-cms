<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminEmailChangeVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $confirmationUrl) {}

    public function build(): self
    {
        return $this->subject('Verify your Rincomm CMS administrator email change')
            ->text('emails.admin-email-change-verification', [
                'confirmationUrl' => $this->confirmationUrl,
            ]);
    }
}
