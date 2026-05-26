<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use App\Models\Presupuesto;

class PresupuestoPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $presupuesto;
    protected $pdfOutput;
    public $customMessage;
    protected $archivosAdjuntos;

    /**
     * Create a new message instance.
     */
    public function __construct(Presupuesto $presupuesto, $pdfOutput, $customMessage = null, array $archivosAdjuntos = [])
    {
        $this->presupuesto = $presupuesto;
        $this->pdfOutput = $pdfOutput;
        $this->customMessage = $customMessage;
        $this->archivosAdjuntos = $archivosAdjuntos;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Presupuesto: ' . ($this->presupuesto->number ?? 'Propuesta'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.presupuestos.pdf',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $this->presupuesto->number ?? $this->presupuesto->id);

        $attachments = [
            Attachment::fromData(fn () => $this->pdfOutput, $safeDocNumber . '.pdf')
                    ->withMime('application/pdf'),
        ];

        foreach ($this->archivosAdjuntos as $archivo) {
            $attachments[] = Attachment::fromPath($archivo->getRealPath())
                                       ->as($archivo->getClientOriginalName())
                                       ->withMime($archivo->getClientMimeType());
        }

        return $attachments;
    }
}
