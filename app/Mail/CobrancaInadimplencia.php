<?php

namespace App\Mail;

use App\Models\Cobranca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CobrancaInadimplencia extends Mailable implements ShouldQueue
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
        $diasAtraso = $this->cobranca->dias_atraso;

        return new Envelope(
            subject: "⚠️ Inadimplência Detectada - {$diasAtraso} dias em atraso",
            tags: ['cobranca', 'inadimplencia', 'atraso'],
            metadata: [
                'cobranca_id' => $this->cobranca->id,
                'dias_atraso' => $diasAtraso,
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
            markdown: 'emails.cobranca.inadimplencia',
            with: [
                'cobranca' => $this->cobranca,
                'usuario' => $this->cobranca->usuario,
                'conta' => $this->cobranca->conta,
                'valor_original' => 'R$ ' . number_format($this->cobranca->valor_fatura, 2, ',', '.'),
                'valor_juros' => 'R$ ' . number_format($this->cobranca->valor_juros, 2, ',', '.'),
                'valor_multa' => 'R$ ' . number_format($this->cobranca->valor_multa, 2, ',', '.'),
                'valor_total' => 'R$ ' . number_format($this->cobranca->valor_total_com_encargos, 2, ',', '.'),
                'dias_atraso' => $this->cobranca->dias_atraso,
                'vencimento_original' => $this->cobranca->vencimento_fatura?->format('d/m/Y'),
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
