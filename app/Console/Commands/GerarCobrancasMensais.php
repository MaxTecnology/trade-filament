<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cobranca;
use App\Models\Conta;
use App\Models\Usuario;
use App\Models\Plano;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GerarCobrancasMensais extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cobrancas:gerar-mensais
                           {--mes= : Mês específico (1-12)}
                           {--ano= : Ano específico}
                           {--dry-run : Executar sem salvar (apenas visualizar)}
                           {--force : Forçar geração mesmo se já existir}';

    /**
     * The console command description.
     */
    protected $description = 'Gera cobranças mensais automáticas baseadas nos planos e configurações das contas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando geração de cobranças mensais...');

        // Configurar parâmetros
        $mes = $this->option('mes') ?? now()->month;
        $ano = $this->option('ano') ?? now()->year;
        $dryRun = $this->option('dry-run') ?? false;
        $force = $this->option('force') ?? false;

        $this->info("📅 Período: {$mes}/{$ano}");
        $this->info("🔍 Modo: " . ($dryRun ? 'DRY RUN' : 'EXECUÇÃO REAL'));

        // Verificar se já existem cobranças para o período
        if (!$force && $this->verificarCobrancasExistentes($mes, $ano)) {
            $this->warn('⚠️ Já existem cobranças para este período. Use --force para sobrescrever.');
            return Command::FAILURE;
        }

        // Buscar contas ativas para cobrança
        $contas = $this->buscarContasParaCobranca();

        if ($contas->isEmpty()) {
            $this->warn('⚠️ Nenhuma conta encontrada para gerar cobranças.');
            return Command::FAILURE;
        }

        $this->info("🏦 Encontradas {$contas->count()} contas para processar");

        // Processar cada conta
        $cobrancasGeradas = 0;
        $valorTotal = 0;
        $erros = [];

        foreach ($contas as $conta) {
            try {
                $cobranca = $this->gerarCobrancaConta($conta, $mes, $ano, $dryRun);

                if ($cobranca) {
                    $cobrancasGeradas++;
                    $valorTotal += $cobranca['valor'];

                    $this->line("✅ {$conta->usuario->nome}: R$ " . number_format($cobranca['valor'], 2, ',', '.'));
                }
            } catch (\Exception $e) {
                $erros[] = "❌ {$conta->usuario->nome}: {$e->getMessage()}";
                $this->error("❌ Erro ao processar conta {$conta->numero_conta}: {$e->getMessage()}");
            }
        }

        // Exibir resumo
        $this->exibirResumo($cobrancasGeradas, $valorTotal, $erros, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * Verificar se já existem cobranças para o período
     */
    private function verificarCobrancasExistentes(int $mes, int $ano): bool
    {
        $referencia = sprintf('%02d/%d', $mes, $ano);

        return Cobranca::where('referencia', 'like', "%{$referencia}%")->exists();
    }

    /**
     * Buscar contas que devem gerar cobranças mensais
     */
    private function buscarContasParaCobranca()
    {
        return Conta::with(['usuario', 'plano', 'tipoConta'])
            ->whereHas('usuario', function ($query) {
                $query->where('status_conta', true)
                    ->where('bloqueado', false);
            })
            ->whereNotNull('plano_id')
            ->get();
    }

    /**
     * Gerar cobrança para uma conta específica
     */
    private function gerarCobrancaConta(Conta $conta, int $mes, int $ano, bool $dryRun): ?array
    {
        // Calcular valor da cobrança
        $valorCobranca = $this->calcularValorCobranca($conta);

        if ($valorCobranca <= 0) {
            return null;
        }

        // Gerar referência
        $referencia = $this->gerarReferencia($conta, $mes, $ano);

        // Calcular data de vencimento
        $vencimento = $this->calcularDataVencimento($conta, $mes, $ano);

        $dadosCobranca = [
            'valor_fatura' => $valorCobranca,
            'referencia' => $referencia,
            'status' => Cobranca::STATUS_PENDENTE,
            'vencimento_fatura' => $vencimento,
            'usuario_id' => $conta->usuario_id,
            'conta_id' => $conta->id,
            'gerente_conta_id' => $conta->gerente_conta_id,
        ];

        // Salvar se não for dry run
        if (!$dryRun) {
            Cobranca::create($dadosCobranca);
        }

        return array_merge($dadosCobranca, ['valor' => $valorCobranca]);
    }

    /**
     * Calcular valor da cobrança baseado no plano e uso
     */
    private function calcularValorCobranca(Conta $conta): float
    {
        $valorBase = 0;

        // Valor do plano
        if ($conta->plano) {
            $valorBase += $conta->plano->valor_mensal ?? 0;
        }

        // Taxa baseada no volume de vendas
        $vendaMensal = $conta->valor_venda_mensal_atual ?? 0;
        if ($vendaMensal > 0) {
            $taxaComissao = ($conta->taxa_repasse_matriz ?? 5) / 100;
            $valorBase += $vendaMensal * $taxaComissao;
        }

        // Taxa de gerenciamento se tiver gerente
        if ($conta->gerente_conta_id) {
            $taxaGerente = ($conta->usuario->taxa_comissao_gerente ?? 2) / 100;
            $valorBase += $vendaMensal * $taxaGerente;
        }

        // Valor mínimo baseado no tipo de conta
        $valorMinimo = $this->obterValorMinimoPorTipo($conta);

        return max($valorBase, $valorMinimo);
    }

    /**
     * Obter valor mínimo baseado no tipo de conta
     */
    private function obterValorMinimoPorTipo(Conta $conta): float
    {
        if (!$conta->tipoConta) {
            return 0;
        }

        return match($conta->tipoConta->nome ?? '') {
            'PF' => 29.90,
            'PJ' => 99.90,
            'FR' => 199.90,
            'MZ' => 499.90,
            default => 0
        };
    }

    /**
     * Gerar referência da cobrança
     */
    private function gerarReferencia(Conta $conta, int $mes, int $ano): string
    {
        $periodo = sprintf('%02d/%d', $mes, $ano);
        $tipoPlano = $conta->plano->nome ?? 'Básico';

        return "Mensalidade {$tipoPlano} - {$periodo}";
    }

    /**
     * Calcular data de vencimento
     */
    private function calcularDataVencimento(Conta $conta, int $mes, int $ano): Carbon
    {
        $diaVencimento = $conta->data_vencimento_fatura ?? 10;

        // Se já passou do dia de vencimento no mês atual, vencer no próximo mês
        $vencimento = Carbon::createFromDate($ano, $mes, $diaVencimento);

        if ($vencimento < now()) {
            $vencimento->addMonth();
        }

        return $vencimento;
    }

    /**
     * Exibir resumo da execução
     */
    private function exibirResumo(int $cobrancasGeradas, float $valorTotal, array $erros, bool $dryRun): void
    {
        $this->info('');
        $this->info('📊 RESUMO DA EXECUÇÃO');
        $this->info('=====================');
        $this->info("📄 Cobranças geradas: {$cobrancasGeradas}");
        $this->info("💰 Valor total: R$ " . number_format($valorTotal, 2, ',', '.'));

        if (!empty($erros)) {
            $this->warn("❌ Erros encontrados: " . count($erros));
            foreach ($erros as $erro) {
                $this->line("   {$erro}");
            }
        }

        if ($dryRun) {
            $this->warn('🔍 EXECUÇÃO EM MODO DRY RUN - Nenhuma cobrança foi salva');
            $this->info('💡 Execute sem --dry-run para salvar as cobranças');
        } else {
            $this->info('✅ Cobranças salvas com sucesso!');
        }
    }
}
