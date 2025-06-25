<?php

namespace App\Notifications;

use App\Models\Cobranca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CobrancaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Cobranca $cobranca,
        public string $tipo,
        public array $dados = []
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Adicionar email se o usuário tem email configurado
        if ($notifiable->email || $notifiable->email_contato) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return match($this->tipo) {
            'alto_valor' => $this->mailAltoValor($notifiable),
            'pagamento' => $this->mailPagamento($notifiable),
            'inadimplencia' => $this->mailInadimplencia($notifiable),
            'vencimento_proximo' => $this->mailVencimentoProximo($notifiable),
            default => $this->mailGenerico($notifiable),
        };
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'tipo' => $this->tipo,
            'cobranca_id' => $this->cobranca->id,
            'referencia' => $this->cobranca->referencia,
            'valor' => $this->cobranca->valor_fatura,
            'status' => $this->cobranca->status,
            'dados' => $this->dados,
            'titulo' => $this->getTitulo(),
            'mensagem' => $this->getMensagem(),
            'url' => route('filament.admin.resources.cobrancas.view', $this->cobranca),
            'created_at' => now(),
        ]);
    }

    /**
     * Mail para alto valor
     */
    private function mailAltoValor(object $notifiable): MailMessage
    {
        $valor = number_format($this->cobranca->valor_fatura, 2, ',', '.');

        return (new MailMessage)
            ->subject("🚨 Cobrança de Alto Valor - R$ {$valor}")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Uma nova cobrança de **alto valor** foi criada e requer sua atenção.")
            ->line("**Referência:** {$this->cobranca->referencia}")
            ->line("**Valor:** R$ {$valor}")
            ->line("**Cliente:** {$this->cobranca->usuario?->nome}")
            ->line("**Vencimento:** {$this->cobranca->vencimento_fatura?->format('d/m/Y')}")
            ->action('Ver Cobrança', route('filament.admin.resources.cobrancas.view', $this->cobranca))
            ->line('Por favor, acompanhe esta cobrança devido ao valor elevado.');
    }

    /**
     * Mail para pagamento
     */
    private function mailPagamento(object $notifiable): MailMessage
    {
        $valor = number_format($this->cobranca->valor_fatura, 2, ',', '.');

        return (new MailMessage)
            ->subject("✅ Pagamento Confirmado - {$this->cobranca->referencia}")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("O pagamento de uma cobrança foi **confirmado** com sucesso.")
            ->line("**Referência:** {$this->cobranca->referencia}")
            ->line("**Valor:** R$ {$valor}")
            ->line("**Cliente:** {$this->cobranca->usuario?->nome}")
            ->line("**Data do Pagamento:** " . now()->format('d/m/Y H:i'))
            ->action('Ver Detalhes', route('filament.admin.resources.cobrancas.view', $this->cobranca))
            ->line('O limite de crédito foi atualizado automaticamente.');
    }

    /**
     * Mail para inadimplência
     */
    private function mailInadimplencia(object $notifiable): MailMessage
    {
        $valor = number_format($this->cobranca->valor_fatura, 2, ',', '.');
        $diasAtraso = $this->cobranca->dias_atraso;

        return (new MailMessage)
            ->subject("⚠️ Inadimplência - {$diasAtraso} dias em atraso")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Uma cobrança está em **inadimplência** e requer ação imediata.")
            ->line("**Referência:** {$this->cobranca->referencia}")
            ->line("**Valor Original:** R$ {$valor}")
            ->line("**Cliente:** {$this->cobranca->usuario?->nome}")
            ->line("**Dias em Atraso:** {$diasAtraso} dias")
            ->line("**Vencimento Original:** {$this->cobranca->vencimento_fatura?->format('d/m/Y')}")
            ->when($this->cobranca->valor_juros > 0, function ($message) {
                $juros = number_format($this->cobranca->valor_juros, 2, ',', '.');
                $multa = number_format($this->cobranca->valor_multa, 2, ',', '.');
                $total = number_format($this->cobranca->valor_total_com_encargos, 2, ',', '.');

                return $message->line("**Juros:** R$ {$juros}")
                    ->line("**Multa:** R$ {$multa}")
                    ->line("**Valor Total:** R$ {$total}");
            })
            ->action('Gerenciar Cobrança', route('filament.admin.resources.cobrancas.edit', $this->cobranca))
            ->line('Entre em contato com o cliente urgentemente.');
    }

    /**
     * Mail para vencimento próximo
     */
    private function mailVencimentoProximo(object $notifiable): MailMessage
    {
        $valor = number_format($this->cobranca->valor_fatura, 2, ',', '.');
        $diasParaVencer = $this->dados['dias_para_vencer'] ?? 'poucos';

        return (new MailMessage)
            ->subject("🔔 Cobrança Vencendo em {$diasParaVencer} dias")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Uma cobrança está próxima do **vencimento**.")
            ->line("**Referência:** {$this->cobranca->referencia}")
            ->line("**Valor:** R$ {$valor}")
            ->line("**Cliente:** {$this->cobranca->usuario?->nome}")
            ->line("**Vencimento:** {$this->cobranca->vencimento_fatura?->format('d/m/Y')}")
            ->line("**Dias para Vencer:** {$diasParaVencer} dias")
            ->action('Ver Cobrança', route('filament.admin.resources.cobrancas.view', $this->cobranca))
            ->line('Considere entrar em contato com o cliente para confirmar o pagamento.');
    }

    /**
     * Mail genérico
     */
    private function mailGenerico(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Notificação de Cobrança - {$this->cobranca->referencia}")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line($this->getMensagem())
            ->action('Ver Cobrança', route('filament.admin.resources.cobrancas.view', $this->cobranca));
    }

    /**
     * Obter título da notificação
     */
    private function getTitulo(): string
    {
        return match($this->tipo) {
            'alto_valor' => 'Cobrança de Alto Valor',
            'pagamento' => 'Pagamento Confirmado',
            'inadimplencia' => 'Inadimplência Detectada',
            'vencimento_proximo' => 'Vencimento Próximo',
            default => 'Notificação de Cobrança',
        };
    }

    /**
     * Obter mensagem da notificação
     */
    private function getMensagem(): string
    {
        $valor = number_format($this->cobranca->valor_fatura, 2, ',', '.');

        return match($this->tipo) {
            'alto_valor' => "Nova cobrança de alto valor (R$ {$valor}) criada para {$this->cobranca->usuario?->nome}",
            'pagamento' => "Pagamento de R$ {$valor} confirmado para {$this->cobranca->referencia}",
            'inadimplencia' => "Cobrança de {$this->cobranca->usuario?->nome} está {$this->cobranca->dias_atraso} dias em atraso",
            'vencimento_proximo' => "Cobrança de R$ {$valor} vence em {$this->dados['dias_para_vencer']} dias",
            default => "Nova notificação sobre cobrança {$this->cobranca->referencia}",
        };
    }
}
