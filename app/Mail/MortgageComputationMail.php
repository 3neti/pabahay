<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Lbhurtado\Mortgage\Models\LoanProfile;

class MortgageComputationMail extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LoanProfile $loanProfile)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Mortgage Computation Results',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mortgage-computation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
