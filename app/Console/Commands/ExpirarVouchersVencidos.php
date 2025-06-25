<?php
// app/Console/Commands/ExpirarVouchersVencidos.php

namespace App\Console\Commands;

use App\Models\Voucher;
use Illuminate\Console\Command;

class ExpirarVouchersVencidos extends Command
{
    protected $signature = 'transacoes:expirar-vouchers
                            {--dry-run : Apenas simular sem salvar}
                            {--dias-alerta=7 : Alertar vouchers que vencem em X dias}';

    protected $description = 'Expirar vouchers vencidos e alertar sobre vencimentos próximos';

    public function handle(): int
    {
        $this->info('🎫 Processando vouchers vencidos...');

        $dryRun = $this->option('dry-run');
        $diasAlerta = (int) $this->option('dias-alerta');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: Nenhuma alteração será salva');
        }

        // Expirar vouchers vencidos
        $vouchersVencidos = Voucher::where('status', 'Ativo')
            ->where('data_expiracao', '<', now())
            ->get();

        $expirados = 0;
        foreach ($vouchersVencidos as $voucher) {
            if (!$dryRun) {
                $voucher->update([
                    'status' => 'Expirado',
                    'observacoes' => 'Expirado automaticamente em ' . now()->format('d/m/Y H:i')
                ]);
            }
            $expirados++;
        }

        // Alertar sobre vencimentos próximos
        $vouchersProximosVencimento = Voucher::where('status', 'Ativo')
            ->whereBetween('data_expiracao', [
                now(),
                now()->addDays($diasAlerta)
            ])
            ->get();

        $this->info("\n📊 RELATÓRIO:");
        $this->line("  Vouchers expirados: {$expirados}");
        $this->line("  Vouchers vencendo em {$diasAlerta} dias: {$vouchersProximosVencimento->count()}");

        if ($vouchersProximosVencimento->count() > 0) {
            $this->warn("\n⚠️  VOUCHERS PRÓXIMOS DO VENCIMENTO:");
            foreach ($vouchersProximosVencimento as $voucher) {
                $valor = $voucher->valor ? 'R$ ' . number_format($voucher->valor, 2, ',', '.') : 'Sem valor';
                $vencimento = $voucher->data_expiracao ? $voucher->data_expiracao->format('d/m/Y') : 'Sem data';
                $this->line("  • {$voucher->codigo} - {$valor} - Vence: {$vencimento}");
            }
        }

        if ($dryRun) {
            $this->warn("\n⚠️  Nenhuma alteração foi salva (modo dry-run)");
        }

        return Command::SUCCESS;
    }
}
