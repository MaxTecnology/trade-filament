<?php
// app/Console/Commands/ProcessarParcelamentosVencidos.php

namespace App\Console\Commands;

use App\Models\Parcelamento;
use App\Models\Transacao;
use Illuminate\Console\Command;

class ProcessarParcelamentosVencidos extends Command
{
    protected $signature = 'transacoes:processar-parcelamentos-vencidos
                            {--dry-run : Apenas simular sem salvar}
                            {--dias-tolerancia=0 : Dias de tolerância antes de marcar como vencida}
                            {--calcular-encargos : Calcular juros e multas automaticamente}';

    protected $description = 'Processar parcelamentos vencidos e calcular encargos';

    public function handle(): int
    {
        $this->info('📅 Processando parcelamentos vencidos...');

        $dryRun = $this->option('dry-run');
        $diasTolerancia = (int) $this->option('dias-tolerancia');
        $calcularEncargos = $this->option('calcular-encargos');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: Nenhuma alteração será salva');
        }

        $dataLimite = now()->subDays($diasTolerancia)->toDateString();

        // Buscar parcelamentos vencidos
        $parcelamentosVencidos = Parcelamento::where('status', 'pendente')
            ->whereDate('data_vencimento', '<', $dataLimite)
            ->with('transacao')
            ->get();

        if ($parcelamentosVencidos->isEmpty()) {
            $this->info('✅ Nenhum parcelamento vencido encontrado');
            return Command::SUCCESS;
        }

        $this->info("📋 Encontrados {$parcelamentosVencidos->count()} parcelamentos vencidos");

        $barra = $this->output->createProgressBar($parcelamentosVencidos->count());
        $barra->start();

        $processados = 0;
        $comEncargos = 0;
        $valorTotalEncargos = 0;

        foreach ($parcelamentosVencidos as $parcelamento) {
            try {
                if (!$dryRun) {
                    $parcelamento->update(['status' => 'vencida']);
                }

                if ($calcularEncargos) {
                    $encargos = $parcelamento->calcularEncargos();

                    if (!$dryRun) {
                        $parcelamento->update([
                            'juros' => $encargos['juros'],
                            'multa' => $encargos['multa']
                        ]);
                    }

                    if ($encargos['juros'] > 0 || $encargos['multa'] > 0) {
                        $comEncargos++;
                        $valorTotalEncargos += $encargos['juros'] + $encargos['multa'];
                    }
                }

                $processados++;

            } catch (\Exception $e) {
                $this->error("\n💥 Erro ao processar parcelamento {$parcelamento->id}: {$e->getMessage()}");
            }

            $barra->advance();
        }

        $barra->finish();

        // Relatório
        $this->info("\n\n📊 RELATÓRIO DO PROCESSAMENTO:");
        $this->line("  Parcelamentos processados: {$processados}");

        if ($calcularEncargos) {
            $this->line("  Com encargos aplicados: {$comEncargos}");
            $this->line("  Valor total dos encargos: R$ " . number_format($valorTotalEncargos, 2, ',', '.'));
        }

        if ($dryRun) {
            $this->warn("⚠️  Nenhuma alteração foi salva (modo dry-run)");
        }

        return Command::SUCCESS;
    }
}
