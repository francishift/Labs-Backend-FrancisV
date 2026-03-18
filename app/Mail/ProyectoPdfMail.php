<?php

namespace App\Mail;

use App\Models\Proyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProyectoPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $proyecto;
    public $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct(Proyecto $proyecto, string $pdfContent)
    {
        $this->proyecto = $proyecto;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Informe de Proyecto - {$this->proyecto->proyecto}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.proyecto-pdf',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "Proyecto-{$this->proyecto->id}.pdf")
                    ->withMime('application/pdf'),
        ];
    }
}
