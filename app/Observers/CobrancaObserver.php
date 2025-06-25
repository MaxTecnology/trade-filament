<?php

namespace App\Observers;

use App\Models\Cobranca;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CobrancaObserver
{
    /**
     * Handle the Cobranca "creating" event.
     */
    public function creating(Cobranca $cobranca): void
    {
        // Validar dados antes da criação
        $this->validarDadosObrigatorios($cobranca);

        // Definir status padrão se não informado
        if (empty($cobranca->status)) {
            $cobranca->status = Cobranca::STATUS_PENDENTE;
        }

        // Definir data de vencimento padrão se não informada
        if (empty($cobranca->vencimento_fatura)) {
            $cobranca->vencimento_fatura = now()->addDays(30);
        }

        // Log da criação
        Log::info('Nova cobrança sendo criada', [
            'referencia' => $cobranca->referencia,
            'valor' => $cobranca->valor_fatura,
            'usuario_id' => $cobranca->usuario_id,
            'conta_id' => $cobranca->conta_id,
            'criado_por' => Auth::id() ?? 'Sistema',
        ]);
    }

    /**
     * Handle the Cobranca "created" event.
     */
    public function created(Cobranca $cobranca): void
    {
        // Atualizar contador de cobranças na conta
        if ($cobranca->conta) {
            $this->atualizarContadorCobrancas($cobranca->conta_id);
        }

        // Verificar se é uma cobrança de alto valor
        if ($cobranca->valor_fatura > 1000) {
            $this->notificarCobrancaAltoValor($cobranca);
        }

        // Log de sucesso
        Log::info('Cobrança criada com sucesso', [
            'id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'valor' => $cobranca->valor_fatura,
        ]);
    }

    /**
     * Handle the Cobranca "updating" event.
     */
    public function updating(Cobranca $cobranca): void
    {
        // Detectar mudanças de status
        if ($cobranca->isDirty('status')) {
            $statusAntigo = $cobranca->getOriginal('status');
            $statusNovo = $cobranca->status;

            $this->processarMudancaStatus($cobranca, $statusAntigo, $statusNovo);
        }

        // Detectar mudanças de valor
        if ($cobranca->isDirty('valor_fatura')) {
            $valorAntigo = $cobranca->getOriginal('valor_fatura');
            $valorNovo = $cobranca->valor_fatura;

            $this->processarMudancaValor($cobranca, $valorAntigo, $valorNovo);
        }

        // Detectar mudanças de vencimento
        if ($cobranca->isDirty('vencimento_fatura')) {
            $this->processarMudancaVencimento($cobranca);
        }
    }

    /**
     * Handle the Cobranca "updated" event.
     */
    public function updated(Cobranca $cobranca): void
    {
        // Atualizar estatísticas da conta se necessário
        if ($cobranca->wasChanged(['status', 'valor_fatura'])) {
            $this->atualizarEstatisticasConta($cobranca);
        }

        // Log de atualização
        $mudancas = $cobranca->getChanges();
        unset($mudancas['updated_at']); // Remover updated_at das mudanças

        if (!empty($mudancas)) {
            Log::info('Cobrança atualizada', [
                'id' => $cobranca->id,
                'referencia' => $cobranca->referencia,
                'mudancas' => $mudancas,
                'atualizado_por' => Auth::id() ?? 'Sistema',
            ]);
        }
    }

    /**
     * Handle the Cobranca "deleting" event.
     */
    public function deleting(Cobranca $cobranca): void
    {
        // Verificar se a cobrança pode ser excluída
        if ($cobranca->status === Cobranca::STATUS_PAGA) {
            Log::warning('Tentativa de excluir cobrança paga', [
                'id' => $cobranca->id,
                'referencia' => $cobranca->referencia,
                'tentativa_por' => Auth::id() ?? 'Sistema',
            ]);

            // Não impedir a exclusão, apenas alertar
        }

        // Log da exclusão
        Log::info('Cobrança sendo excluída', [
            'id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'status' => $cobranca->status,
            'valor' => $cobranca->valor_fatura,
            'excluido_por' => Auth::id() ?? 'Sistema',
        ]);
    }

    /**
     * Handle the Cobranca "deleted" event.
     */
    public function deleted(Cobranca $cobranca): void
    {
        // Atualizar contador de cobranças na conta
        if ($cobranca->conta_id) {
            $this->atualizarContadorCobrancas($cobranca->conta_id);
        }

        // Log de confirmação
        Log::info('Cobrança excluída com sucesso', [
            'id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
        ]);
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Validar dados obrigatórios antes da criação
     */
    private function validarDadosObrigatorios(Cobranca $cobranca): void
    {
        $erros = [];

        // Validar valor
        if (!$cobranca->valor_fatura || $cobranca->valor_fatura <= 0) {
            $erros[] = 'Valor da fatura deve ser maior que zero';
        }

        // Validar referência
        if (empty($cobranca->referencia)) {
            $cobranca->referencia = 'Cobrança ' . now()->format('m/Y');
        }

        // Validar pelo menos um relacionamento
        if (!$cobranca->usuario_id && !$cobranca->conta_id && !$cobranca->transacao_id) {
            $erros[] = 'Cobrança deve estar vinculada a um usuário, conta ou transação';
        }

        if (!empty($erros)) {
            Log::error('Erro na validação de cobrança', [
                'erros' => $erros,
                'dados' => $cobranca->toArray(),
            ]);

            throw new \InvalidArgumentException(implode(', ', $erros));
        }
    }

    /**
     * Processar mudança de status
     */
    private function processarMudancaStatus(Cobranca $cobranca, string $statusAntigo, string $statusNovo): void
    {
        Log::info('Mudança de status detectada', [
            'cobranca_id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'status_antigo' => $statusAntigo,
            'status_novo' => $statusNovo,
            'alterado_por' => Auth::id() ?? 'Sistema',
        ]);

        // Processar transições específicas
        switch ($statusNovo) {
            case Cobranca::STATUS_PAGA:
                $this->processarPagamento($cobranca);
                break;

            case Cobranca::STATUS_VENCIDA:
                $this->processarVencimento($cobranca);
                break;

            case Cobranca::STATUS_CANCELADA:
                $this->processarCancelamento($cobranca);
                break;
        }
    }

    /**
     * Processar pagamento da cobrança
     */
    private function processarPagamento(Cobranca $cobranca): void
    {
        // Atualizar limite de crédito se aplicável
        if ($cobranca->conta && $cobranca->conta->limite_utilizado >= $cobranca->valor_fatura) {
            $conta = $cobranca->conta;
            $conta->update([
                'limite_utilizado' => $conta->limite_utilizado - $cobranca->valor_fatura,
                'limite_disponivel' => $conta->limite_credito - ($conta->limite_utilizado - $cobranca->valor_fatura),
            ]);

            Log::info('Limite de crédito atualizado após pagamento', [
                'conta_id' => $conta->id,
                'valor_liberado' => $cobranca->valor_fatura,
                'novo_limite_disponivel' => $conta->limite_disponivel,
            ]);
        }

        // Notificar gerente se for valor alto
        if ($cobranca->valor_fatura > 500 && $cobranca->gerente) {
            $this->notificarGerentePagamento($cobranca);
        }
    }

    /**
     * Processar vencimento da cobrança
     */
    private function processarVencimento(Cobranca $cobranca): void
    {
        // Calcular e logar encargos
        $juros = $cobranca->valor_juros;
        $multa = $cobranca->valor_multa;

        if ($juros > 0 || $multa > 0) {
            Log::warning('Cobrança vencida com encargos', [
                'cobranca_id' => $cobranca->id,
                'referencia' => $cobranca->referencia,
                'valor_original' => $cobranca->valor_fatura,
                'juros' => $juros,
                'multa' => $multa,
                'valor_total' => $cobranca->valor_total_com_encargos,
                'dias_atraso' => $cobranca->dias_atraso,
            ]);
        }

        // Notificar gerente sobre inadimplência
        if ($cobranca->gerente) {
            $this->notificarGerenteInadimplencia($cobranca);
        }
    }

    /**
     * Processar cancelamento da cobrança
     */
    private function processarCancelamento(Cobranca $cobranca): void
    {
        Log::info('Cobrança cancelada', [
            'cobranca_id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'valor' => $cobranca->valor_fatura,
            'motivo' => 'Cancelamento via sistema',
            'cancelado_por' => Auth::id() ?? 'Sistema',
        ]);
    }

    /**
     * Processar mudança de valor
     */
    private function processarMudancaValor(Cobranca $cobranca, float $valorAntigo, float $valorNovo): void
    {
        $diferenca = $valorNovo - $valorAntigo;

        Log::info('Valor da cobrança alterado', [
            'cobranca_id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'valor_antigo' => $valorAntigo,
            'valor_novo' => $valorNovo,
            'diferenca' => $diferenca,
            'alterado_por' => Auth::id() ?? 'Sistema',
        ]);

        // Notificar se for aumento significativo (>20%)
        if ($diferenca > ($valorAntigo * 0.2)) {
            Log::warning('Aumento significativo no valor da cobrança', [
                'cobranca_id' => $cobranca->id,
                'percentual_aumento' => round(($diferenca / $valorAntigo) * 100, 2),
            ]);
        }
    }

    /**
     * Processar mudança de vencimento
     */
    private function processarMudancaVencimento(Cobranca $cobranca): void
    {
        $vencimentoAntigo = $cobranca->getOriginal('vencimento_fatura');
        $vencimentoNovo = $cobranca->vencimento_fatura;

        Log::info('Data de vencimento alterada', [
            'cobranca_id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'vencimento_antigo' => $vencimentoAntigo,
            'vencimento_novo' => $vencimentoNovo,
            'alterado_por' => Auth::id() ?? 'Sistema',
        ]);
    }

    /**
     * Atualizar contador de cobranças na conta
     */
    private function atualizarContadorCobrancas(int $contaId): void
    {
        try {
            $conta = \App\Models\Conta::find($contaId);
            if (!$conta) return;

            // Aqui você pode implementar campos específicos na tabela contas
            // Por exemplo: total_cobrancas, valor_total_cobrancas, etc.

            Log::debug('Contador de cobranças atualizado', [
                'conta_id' => $contaId,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar contador de cobranças', [
                'conta_id' => $contaId,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Atualizar estatísticas da conta
     */
    private function atualizarEstatisticasConta(Cobranca $cobranca): void
    {
        if (!$cobranca->conta) return;

        try {
            // Calcular totais da conta
            $totalPendente = Cobranca::where('conta_id', $cobranca->conta_id)
                ->where('status', Cobranca::STATUS_PENDENTE)
                ->sum('valor_fatura');

            $totalVencido = Cobranca::where('conta_id', $cobranca->conta_id)
                ->where('status', Cobranca::STATUS_VENCIDA)
                ->sum('valor_fatura');

            Log::debug('Estatísticas da conta atualizadas', [
                'conta_id' => $cobranca->conta_id,
                'total_pendente' => $totalPendente,
                'total_vencido' => $totalVencido,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar estatísticas da conta', [
                'conta_id' => $cobranca->conta_id,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notificar cobrança de alto valor
     */
    private function notificarCobrancaAltoValor(Cobranca $cobranca): void
    {
        \App\Services\NotificationService::notificarAltoValor($cobranca);
    }

    /**
     * Notificar gerente sobre pagamento
     */
    private function notificarGerentePagamento(Cobranca $cobranca): void
    {
        \App\Services\NotificationService::notificarPagamento($cobranca);
    }

    /**
     * Notificar gerente sobre inadimplência
     */
    private function notificarGerenteInadimplencia(Cobranca $cobranca): void
    {
        \App\Services\NotificationService::notificarInadimplencia($cobranca);
    }
}
