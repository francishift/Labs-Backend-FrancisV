<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use App\Models\Factura;

class FacturaPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $factura;
    protected $pdfOutput;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Factura $factura, $pdfOutput, $customMessage = null)
    {
        $this->factura = $factura;
        $this->pdfOutput = $pdfOutput;
        $this->customMessage = $customMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura: ' . ($this->factura->number ?? 'Propuesta'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.facturas.pdf',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $safeDocNumber = str_replace(['/', '\\'], '-', $this->factura->number ?? $this->factura->id);

        return [
            Attachment::fromData(fn () => $this->pdfOutput, $safeDocNumber . '.pdf')
                    ->withMime('application/pdf'),
        ];
    }
}
