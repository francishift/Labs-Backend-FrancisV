<?php

namespace App\Mail;

use App\Models\Mantenimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MantenimientoPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mantenimiento;
    public $pdfContent;
    public $month;
    public $year;

    /**
     * Create a new message instance.
     */
    public function __construct(Mantenimiento $mantenimiento, string $pdfContent, $month, $year)
    {
        $this->mantenimiento = $mantenimiento;
        $this->pdfContent = $pdfContent;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $periodo = $this->month === 'all' ? "Año {$this->year}" : \Carbon\Carbon::create()->month((int)$this->month)->locale('es')->monthName . " {$this->year}";
        
        return new Envelope(
            subject: "Informe de Mantenimiento - {$this->mantenimiento->aplicacion} ({$periodo})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.mantenimiento-pdf',
            with: [
                'periodoTexto' => $this->month === 'all' 
                    ? "el año {$this->year}" 
                    : "el mes de " . \Carbon\Carbon::create()->month((int)$this->month)->locale('es')->monthName . " de {$this->year}",
            ],
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
            Attachment::fromData(fn () => $this->pdfContent, "Mantenimiento-{$this->mantenimiento->id}.pdf")
                    ->withMime('application/pdf'),
        ];
    }
}
