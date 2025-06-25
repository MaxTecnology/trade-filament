<?php

namespace App\Mail;

use App\Models\Cobranca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CobrancaPagamento extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Cobranca $cobranca
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Pagamento Confirmado - ' . $this->cobranca->referencia,
            tags: ['cobranca', 'pagamento', 'confirmacao'],
            metadata: [
                'cobranca_id' => $this->cobranca->id,
                'valor' => $this->cobranca->valor_fatura,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cobranca.pagamento',
            with: [
                'cobranca' => $this->cobranca,
                'usuario' => $this->cobranca->usuario,
                'conta' => $this->cobranca->conta,
                'valor_formatado' => 'R$ ' . number_format($this->cobranca->valor_fatura, 2, ',', '.'),
                'data_pagamento' => now()->format('d/m/Y H:i'),
                'url_cobranca' => route('filament.admin.resources.cobrancas.view', $this->cobranca),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
