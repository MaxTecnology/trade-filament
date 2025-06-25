<?php
// app/Console/Commands/ProcessarTransacoesPendentes.php

namespace App\Console\Commands;

use App\Models\Transacao;
use App\Models\Parcelamento;
use App\Models\Voucher;
use Illuminate\Console\Command;

class ProcessarTransacoesPendentes extends Command
{
    protected $signature = 'transacoes:processar-pendentes
                            {--dry-run : Apenas simular sem salvar}
                            {--limit=50 : Limite de transações para processar}
                            {--auto-approve : Aprovar automaticamente transações elegíveis}';

    protected $description = 'Processar transações pendentes e criar relacionamentos';

    public function handle(): int
    {
        $this->info('🚀 Iniciando processamento de transações pendentes...');

        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $autoApprove = $this->option('auto-approve');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: Nenhuma alteração será salva');
        }

        // Buscar transações pendentes
        $transacoesPendentes = Transacao::pendentes()
            ->with(['comprador', 'vendedor', 'oferta'])
            ->limit($limit)
            ->get();

        if ($transacoesPendentes->isEmpty()) {
            $this->info('✅ Nenhuma transação pendente encontrada');
            return Command::SUCCESS;
        }

        $this->info("📋 Encontradas {$transacoesPendentes->count()} transações pendentes");

        $barra = $this->output->createProgressBar($transacoesPendentes->count());
        $barra->start();

        $processadas = 0;
        $aprovadas = 0;
        $erros = 0;

        foreach ($transacoesPendentes as $transacao) {
            try {
                // Validar transação
                $validacao = $this->validarTransacao($transacao);

                if (!$validacao['valida']) {
                    $this->warn("\n❌ Transação {$transacao->codigo}: {$validacao['erro']}");
                    $erros++;
                    continue;
                }

                // Auto-aprovar se solicitado
                if ($autoApprove && $this->podeSerAprovadaAutomaticamente($transacao)) {
                    if (!$dryRun) {
                        $transacao->marcarComoAprovada();
                        $this->criarRelacionamentosDependentes($transacao);
                    }
                    $aprovadas++;
                }

                $processadas++;

            } catch (\Exception $e) {
                $this->error("\n💥 Erro ao processar transação {$transacao->codigo}: {$e->getMessage()}");
                $erros++;
            }

            $barra->advance();
        }

        $barra->finish();

        // Relatório final
        $this->info("\n\n📊 RELATÓRIO DO PROCESSAMENTO:");
        $this->line("  Transações processadas: {$processadas}");
        $this->line("  Aprovadas automaticamente: {$aprovadas}");
        $this->line("  Erros encontrados: {$erros}");

        if ($dryRun) {
            $this->warn("⚠️  Nenhuma alteração foi salva (modo dry-run)");
        }

        return Command::SUCCESS;
    }

    private function validarTransacao(Transacao $transacao): array
    {
        // Validar relacionamentos
        $errosRelacionamento = $transacao->validarRelacionamentos();
        if (!empty($errosRelacionamento)) {
            return ['valida' => false, 'erro' => implode(', ', $errosRelacionamento)];
        }

        // Validar regras de negócio
        $errosNegocio = $transacao->validarRegrasDeNegocio();
        if (!empty($errosNegocio)) {
            return ['valida' => false, 'erro' => implode(', ', $errosNegocio)];
        }

        return ['valida' => true, 'erro' => null];
    }

    private function podeSerAprovadaAutomaticamente(Transacao $transacao): bool
    {
        // Critérios para aprovação automática
        return $transacao->valor_rt <= 1000 && // Até R$ 1.000
            $transacao->comprador &&
            $transacao->vendedor &&
            $transacao->comprador->status_conta &&
            $transacao->vendedor->status_conta;
    }

    private function criarRelacionamentosDependentes(Transacao $transacao): void
    {
        // Criar parcelamentos se necessário
        if ($transacao->numero_parcelas > 1) {
            $this->criarParcelamentos($transacao);
        }

        // Criar voucher se necessário
        if ($transacao->emite_voucher) {
            $this->criarVoucher($transacao);
        }
    }

    private function criarParcelamentos(Transacao $transacao): void
    {
        $valorParcela = round($transacao->valor_rt / $transacao->numero_parcelas, 2);
        $valorUltimaParcela = $transacao->valor_rt - ($valorParcela * ($transacao->numero_parcelas - 1));

        $comissaoParcela = round($transacao->comissao / $transacao->numero_parcelas, 2);
        $comissaoUltimaParcela = $transacao->comissao - ($comissaoParcela * ($transacao->numero_parcelas - 1));

        for ($i = 1; $i <= $transacao->numero_parcelas; $i++) {
            Parcelamento::create([
                'transacao_id' => $transacao->id,
                'numero_parcela' => $i,
                'valor_parcela' => ($i === $transacao->numero_parcelas) ? $valorUltimaParcela : $valorParcela,
                'comissao_parcela' => ($i === $transacao->numero_parcelas) ? $comissaoUltimaParcela : $comissaoParcela,
                'data_vencimento' => now()->addMonths($i - 1),
                'status' => $i === 1 ? 'paga' : 'pendente', // Primeira parcela como paga
            ]);
        }
    }

    private function criarVoucher(Transacao $transacao): void
    {
        Voucher::create([
            'transacao_id' => $transacao->id,
            'valor' => $transacao->valor_rt,
            'data_expiracao' => now()->addMonths(6),
            'status' => 'Ativo',
        ]);
    }
}
