<?php

namespace App\Mail;

use App\Models\VerificationChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
        public readonly int $expiresInMinutes
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForPurpose(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
            with: [
                'code' => $this->code,
                'purposeLabel' => $this->purposeLabel(),
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function purposeLabel(): string
    {
        return match ($this->purpose) {
            VerificationChallenge::PURPOSE_REGISTRATION =>
            'account registration',

            VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION =>
            'employee account activation',

            VerificationChallenge::PURPOSE_PASSWORD_RESET =>
            'password reset',

            default =>
            'account verification',
        };
    }

    private function subjectForPurpose(): string
    {
        $applicationName = config('app.name', 'Rincomm');

        return match ($this->purpose) {
            VerificationChallenge::PURPOSE_REGISTRATION =>
            "{$applicationName} registration verification code",

            VerificationChallenge::PURPOSE_EMPLOYEE_ACTIVATION =>
            "{$applicationName} employee activation code",

            VerificationChallenge::PURPOSE_PASSWORD_RESET =>
            "{$applicationName} password reset code",

            default =>
            "{$applicationName} verification code",
        };
    }
}
