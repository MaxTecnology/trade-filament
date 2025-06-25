<?php
// app/Console/Commands/RelatorioTransacoes.php

namespace App\Console\Commands;

use App\Models\Transacao;
use App\Models\Parcelamento;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RelatorioTransacoes extends Command
{
    protected $signature = 'transacoes:relatorio
                            {--periodo=mes : Período do relatório (mes, ano, tudo)}
                            {--mes= : Mês específico (1-12)}
                            {--ano= : Ano específico}
                            {--exportar : Exportar para arquivo}';

    protected $description = 'Gerar relatório completo de transações';

    public function handle(): int
    {
        $this->info('📊 Gerando relatório de transações...');

        $periodo = $this->option('periodo');
        $mes = $this->option('mes');
        $ano = $this->option('ano');

        // Construir query baseada no período
        $query = Transacao::query();

        switch ($periodo) {
            case 'mes':
                $mes = $mes ?? now()->month;
                $ano = $ano ?? now()->year;
                $query->doMes($mes, $ano);
                $tituloPeriodo = "Mês {$mes}/{$ano}";
                break;

            case 'ano':
                $ano = $ano ?? now()->year;
                $query->doAno($ano);
                $tituloPeriodo = "Ano {$ano}";
                break;

            default:
                $tituloPeriodo = "Todo o período";
                break;
        }

        // Estatísticas gerais
        $this->exibirEstatisticasGerais($query, $tituloPeriodo);

        // Estatísticas por status
        $this->exibirEstatisticasPorStatus($query);

        // Top compradores e vendedores
        $this->exibirTopUsuarios($query);

        // Estatísticas de parcelamentos
        $this->exibirEstatisticasParcelamentos($query);

        // Estatísticas de vouchers
        $this->exibirEstatisticasVouchers($query);

        return Command::SUCCESS;
    }

    private function exibirEstatisticasGerais($query, string $periodo): void
    {
        $resumo = $query->selectRaw('
            COUNT(*) as total,
            SUM(valor_rt) as volume_total,
            AVG(valor_rt) as ticket_medio,
            SUM(comissao) as comissoes_total,
            AVG(nota_atendimento) as nota_media
        ')->first();

        $this->info("\n🎯 RESUMO GERAL - {$periodo}");
        $this->line("  Total de transações: " . number_format($resumo->total ?? 0));
        $this->line("  Volume total: R$ " . number_format($resumo->volume_total ?? 0, 2, ',', '.'));
        $this->line("  Ticket médio: R$ " . number_format($resumo->ticket_medio ?? 0, 2, ',', '.'));
        $this->line("  Comissões total: R$ " . number_format($resumo->comissoes_total ?? 0, 2, ',', '.'));
        $this->line("  Nota média: " . number_format($resumo->nota_media ?? 0, 2));
    }

    private function exibirEstatisticasPorStatus($query): void
    {
        $porStatus = $query->selectRaw('status, COUNT(*) as quantidade, SUM(valor_rt) as volume')
            ->groupBy('status')
            ->get();

        $this->info("\n📈 ESTATÍSTICAS POR STATUS:");
        foreach ($porStatus as $stat) {
            $percentual = $porStatus->sum('quantidade') > 0 ?
                round(($stat->quantidade / $porStatus->sum('quantidade')) * 100, 1) : 0;

            $this->line("  {$stat->status}: {$stat->quantidade} ({$percentual}%) - R$ " .
                number_format($stat->volume, 2, ',', '.'));
        }
    }

    private function exibirTopUsuarios($query): void
    {
        $topCompradores = $query->select('nome_comprador')
            ->selectRaw('COUNT(*) as total, SUM(valor_rt) as volume')
            ->where('status', 'aprovada')
            ->groupBy('nome_comprador')
            ->orderBy('volume', 'desc')
            ->limit(5)
            ->get();

        $this->info("\n🏆 TOP 5 COMPRADORES:");
        foreach ($topCompradores as $index => $comprador) {
            $this->line("  " . ($index + 1) . ". {$comprador->nome_comprador} - {$comprador->total} transações - R$ " .
                number_format($comprador->volume, 2, ',', '.'));
        }

        $topVendedores = $query->select('nome_vendedor')
            ->selectRaw('COUNT(*) as total, SUM(valor_rt) as volume')
            ->where('status', 'aprovada')
            ->groupBy('nome_vendedor')
            ->orderBy('volume', 'desc')
            ->limit(5)
            ->get();

        $this->info("\n🏆 TOP 5 VENDEDORES:");
        foreach ($topVendedores as $index => $vendedor) {
            $this->line("  " . ($index + 1) . ". {$vendedor->nome_vendedor} - {$vendedor->total} transações - R$ " .
                number_format($vendedor->volume, 2, ',', '.'));
        }
    }

    private function exibirEstatisticasParcelamentos($query): void
    {
        // Verificar se a tabela existe
        if (!Schema::hasTable('parcelamentos')) {
            $this->warn("\n⚠️  Tabela de parcelamentos não encontrada");
            return;
        }

        try {
            // Criar nova query baseada nos mesmos critérios
            $periodo = $this->option('periodo');
            $mes = $this->option('mes');
            $ano = $this->option('ano');

            $transacaoQuery = Transacao::query();

            switch ($periodo) {
                case 'mes':
                    $mes = $mes ?? now()->month;
                    $ano = $ano ?? now()->year;
                    $transacaoQuery->doMes($mes, $ano);
                    break;
                case 'ano':
                    $ano = $ano ?? now()->year;
                    $transacaoQuery->doAno($ano);
                    break;
            }

            $transacaoIds = $transacaoQuery->pluck('id');

            if ($transacaoIds->isEmpty()) {
                $this->line("\n💳 PARCELAMENTOS:");
                $this->line("  Nenhuma transação encontrada para análise");
                return;
            }

            $parcelamentos = Parcelamento::whereIn('transacao_id', $transacaoIds)
                ->selectRaw('status, COUNT(*) as quantidade, SUM(valor_parcela) as valor')
                ->groupBy('status')
                ->get();

            $this->info("\n💳 PARCELAMENTOS:");
            if ($parcelamentos->isEmpty()) {
                $this->line("  Nenhum parcelamento encontrado");
            } else {
                foreach ($parcelamentos as $parc) {
                    $this->line("  {$parc->status}: {$parc->quantidade} parcelas - R$ " .
                        number_format($parc->valor ?? 0, 2, ',', '.'));
                }
            }
        } catch (\Exception $e) {
            $this->warn("\n⚠️  Erro ao buscar parcelamentos: " . $e->getMessage());
            $this->line("💳 PARCELAMENTOS: Dados indisponíveis");
        }
    }

    private function exibirEstatisticasVouchers($query): void
    {
        // Verificar se a tabela existe
        if (!Schema::hasTable('vouchers')) {
            $this->warn("\n⚠️  Tabela de vouchers não encontrada");
            return;
        }

        try {
            // Criar nova query baseada nos mesmos critérios
            $periodo = $this->option('periodo');
            $mes = $this->option('mes');
            $ano = $this->option('ano');

            $transacaoQuery = Transacao::query();

            switch ($periodo) {
                case 'mes':
                    $mes = $mes ?? now()->month;
                    $ano = $ano ?? now()->year;
                    $transacaoQuery->doMes($mes, $ano);
                    break;
                case 'ano':
                    $ano = $ano ?? now()->year;
                    $transacaoQuery->doAno($ano);
                    break;
            }

            $transacaoIds = $transacaoQuery->pluck('id');

            if ($transacaoIds->isEmpty()) {
                $this->line("\n🎫 VOUCHERS:");
                $this->line("  Nenhuma transação encontrada para análise");
                return;
            }

            $vouchers = Voucher::whereIn('transacao_id', $transacaoIds)
                ->selectRaw('status, COUNT(*) as quantidade, SUM(COALESCE(valor, 0)) as valor')
                ->groupBy('status')
                ->get();

            $this->info("\n🎫 VOUCHERS:");
            if ($vouchers->isEmpty()) {
                $this->line("  Nenhum voucher encontrado");
            } else {
                foreach ($vouchers as $voucher) {
                    $this->line("  {$voucher->status}: {$voucher->quantidade} vouchers - R$ " .
                        number_format($voucher->valor ?? 0, 2, ',', '.'));
                }
            }
        } catch (\Exception $e) {
            $this->warn("\n⚠️  Erro ao buscar vouchers: " . $e->getMessage());
            $this->line("🎫 VOUCHERS: Dados indisponíveis");
        }
    }
}
