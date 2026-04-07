<?php

namespace App\Mail;

use App\Models\Invitacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitacionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invitacion $invitacion) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a Selcosi Flota',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitacion',
            with: [
                'url' => route('registro.invitacion', $this->invitacion->token),
                'expira' => $this->invitacion->expira_en->format('d/m/Y'),
            ],
        );
    }
}
