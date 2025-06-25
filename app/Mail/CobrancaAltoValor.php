<?php

namespace App\Mail;

use App\Models\Cobranca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CobrancaAltoValor extends Mailable implements ShouldQueue
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
            subject: '🚨 Alerta: Cobrança de Alto Valor Criada - R$ ' . number_format($this->cobranca->valor_fatura, 2, ',', '.'),
            tags: ['cobranca', 'alto-valor', 'alerta'],
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
            markdown: 'emails.cobranca.alto-valor',
            with: [
                'cobranca' => $this->cobranca,
                'usuario' => $this->cobranca->usuario,
                'conta' => $this->cobranca->conta,
                'gerente' => $this->cobranca->gerente,
                'valor_formatado' => 'R$ ' . number_format($this->cobranca->valor_fatura, 2, ',', '.'),
                'vencimento_formatado' => $this->cobranca->vencimento_fatura?->format('d/m/Y'),
                'url_cobranca' => route('filament.admin.resources.cobrancas.edit', $this->cobranca),
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
