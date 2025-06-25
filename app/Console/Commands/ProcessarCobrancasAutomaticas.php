<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cobranca;
use App\Models\Usuario;
use App\Models\Conta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessarCobrancasAutomaticas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cobrancas:processar-automaticas
                           {--dry-run : Executar sem salvar (apenas visualizar)}
                           {--skip-gerar : Pular geração de novas cobranças}
                           {--skip-vencidas : Pular marcação de vencidas}
                           {--skip-limpeza : Pular limpeza de dados}
                           {--force : Forçar execução mesmo em ambiente de produção}
                           {--relatorio : Gerar apenas relatório sem processar}';

    /**
     * The console command description.
     */
    protected $description = 'Processa todas as rotinas automáticas de cobrança (geração, vencimentos, limpeza)';

    private bool $dryRun = false;
    private array $resultados = [];
    private array $configuracao = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inicio = microtime(true);

        $this->info('🚀 Iniciando processamento automático de cobranças...');
        $this->info('📅 Data/Hora: ' . now()->format('d/m/Y H:i:s'));

        // Configurar parâmetros
        $this->configurarParametros();

        // Verificar ambiente de produção
        if (!$this->verificarAmbiente()) {
            return Command::FAILURE;
        }

        // Executar apenas relatório se solicitado
        if ($this->option('relatorio')) {
            return $this->gerarRelatorioCompleto();
        }

        // Executar processos principais
        $this->executarProcessosPrincipais();

        // Executar limpezas e manutenções
        $this->executarLimpezas();

        // Gerar relatório final
        $this->gerarRelatorioFinal($inicio);

        return Command::SUCCESS;
    }

    /**
     * Configurar parâmetros do command
     */
    private function configurarParametros(): void
    {
        $this->dryRun = $this->option('dry-run') ?? false;

        $this->configuracao = [
            'gerar_mensais' => !$this->option('skip-gerar'),
            'marcar_vencidas' => !$this->option('skip-vencidas'),
            'executar_limpeza' => !$this->option('skip-limpeza'),
            'force' => $this->option('force') ?? false,
        ];

        $this->info("⚙️ Configurações:");
        $this->info("   🔍 Modo: " . ($this->dryRun ? 'DRY RUN' : 'EXECUÇÃO REAL'));
        $this->info("   📊 Gerar mensais: " . ($this->configuracao['gerar_mensais'] ? 'SIM' : 'NÃO'));
        $this->info("   ⚠️ Marcar vencidas: " . ($this->configuracao['marcar_vencidas'] ? 'SIM' : 'NÃO'));
        $this->info("   🧹 Executar limpeza: " . ($this->configuracao['executar_limpeza'] ? 'SIM' : 'NÃO'));
    }

    /**
     * Verificar ambiente de execução
     */
    private function verificarAmbiente(): bool
    {
        if (app()->environment('production') && !$this->configuracao['force']) {
            $this->warn('⚠️ Ambiente de PRODUÇÃO detectado!');

            if (!$this->confirm('Confirma execução em ambiente de produção?', false)) {
                $this->error('❌ Execução cancelada pelo usuário');
                return false;
            }
        }

        return true;
    }

    /**
     * Executar processos principais
     */
    private function executarProcessosPrincipais(): void
    {
        $this->info('');
        $this->info('🔄 EXECUTANDO PROCESSOS PRINCIPAIS');
        $this->info('==================================');

        // 1. Gerar cobranças mensais (se for primeiro dia do mês ou forçado)
        if ($this->configuracao['gerar_mensais'] && $this->deveGerarMensais()) {
            $this->executarGeracaoMensal();
        }

        // 2. Marcar cobranças vencidas
        if ($this->configuracao['marcar_vencidas']) {
            $this->executarMarcacaoVencidas();
        }

        // 3. Atualizar saldos e limites
        $this->atualizarSaldosLimites();

        // 4. Processar pagamentos pendentes
        $this->processarPagamentosPendentes();
    }

    /**
     * Verificar se deve gerar mensais
     */
    private function deveGerarMensais(): bool
    {
        // Gerar nos primeiros 5 dias do mês
        $diaAtual = now()->day;

        return $diaAtual <= 5 || $this->configuracao['force'];
    }

    /**
     * Executar geração de cobranças mensais
     */
    private function executarGeracaoMensal(): void
    {
        $this->info('📋 Gerando cobranças mensais...');

        $parametros = [
            '--mes' => now()->month,
            '--ano' => now()->year,
        ];

        if ($this->dryRun) {
            $parametros['--dry-run'] = true;
        }

        $exitCode = Artisan::call('cobrancas:gerar-mensais', $parametros);

        $this->resultados['geracao_mensal'] = [
            'executado' => true,
            'sucesso' => $exitCode === 0,
            'output' => Artisan::output(),
        ];

        if ($exitCode === 0) {
            $this->info('✅ Geração mensal concluída');
        } else {
            $this->error('❌ Erro na geração mensal');
        }
    }

    /**
     * Executar marcação de vencidas
     */
    private function executarMarcacaoVencidas(): void
    {
        $this->info('⚠️ Marcando cobranças vencidas...');

        $parametros = [
            '--dias' => 0, // Sem tolerância para automático
            '--incluir-em-analise' => true,
        ];

        if ($this->dryRun) {
            $parametros['--dry-run'] = true;
        }

        $exitCode = Artisan::call('cobrancas:marcar-vencidas', $parametros);

        $this->resultados['marcacao_vencidas'] = [
            'executado' => true,
            'sucesso' => $exitCode === 0,
            'output' => Artisan::output(),
        ];

        if ($exitCode === 0) {
            $this->info('✅ Marcação de vencidas concluída');
        } else {
            $this->error('❌ Erro na marcação de vencidas');
        }
    }

    /**
     * Atualizar saldos e limites das contas
     */
    private function atualizarSaldosLimites(): void
    {
        $this->info('💰 Atualizando saldos e limites...');

        try {
            $contasAtualizadas = 0;

            if (!$this->dryRun) {
                // Atualizar limite disponível baseado no utilizado
                $contasAtualizadas = DB::table('contas')
                    ->whereColumn('limite_disponivel', '!=', DB::raw('limite_credito - limite_utilizado'))
                    ->update([
                        'limite_disponivel' => DB::raw('limite_credito - limite_utilizado'),
                        'updated_at' => now(),
                    ]);
            } else {
                // Simular contagem para dry run
                $contasAtualizadas = Conta::whereColumn('limite_disponivel', '!=', DB::raw('limite_credito - limite_utilizado'))->count();
            }

            $this->resultados['atualizacao_saldos'] = [
                'executado' => true,
                'sucesso' => true,
                'contas_atualizadas' => $contasAtualizadas,
            ];

            $this->info("✅ {$contasAtualizadas} contas atualizadas");

        } catch (\Exception $e) {
            $this->error("❌ Erro ao atualizar saldos: {$e->getMessage()}");

            $this->resultados['atualizacao_saldos'] = [
                'executado' => true,
                'sucesso' => false,
                'erro' => $e->getMessage(),
            ];
        }
    }

    /**
     * Processar pagamentos pendentes automaticamente
     */
    private function processarPagamentosPendentes(): void
    {
        $this->info('💳 Processando pagamentos automáticos...');

        try {
            // Buscar cobranças que podem ser pagas automaticamente
            // (por exemplo, com débito automático configurado)
            $cobrancasAutomaticas = Cobranca::where('status', Cobranca::STATUS_PENDENTE)
                ->whereHas('conta', function ($query) {
                    $query->whereJsonContains('permissoes_especificas->debito_automatico', true);
                })
                ->limit(10) // Processar até 10 por vez
                ->get();

            $pagamentosProcessados = 0;

            foreach ($cobrancasAutomaticas as $cobranca) {
                if ($this->tentarPagamentoAutomatico($cobranca)) {
                    $pagamentosProcessados++;
                }
            }

            $this->resultados['pagamentos_automaticos'] = [
                'executado' => true,
                'sucesso' => true,
                'processados' => $pagamentosProcessados,
            ];

            $this->info("✅ {$pagamentosProcessados} pagamentos processados automaticamente");

        } catch (\Exception $e) {
            $this->error("❌ Erro ao processar pagamentos: {$e->getMessage()}");

            $this->resultados['pagamentos_automaticos'] = [
                'executado' => true,
                'sucesso' => false,
                'erro' => $e->getMessage(),
            ];
        }
    }

    /**
     * Tentar pagamento automático de uma cobrança
     */
    private function tentarPagamentoAutomatico(Cobranca $cobranca): bool
    {
        if ($this->dryRun) {
            return true; // Simular sucesso em dry run
        }

        try {
            // Verificar se a conta tem saldo suficiente
            $conta = $cobranca->conta;
            $saldoTotal = $conta->saldo_permuta + $conta->saldo_dinheiro;

            if ($saldoTotal >= $cobranca->valor_fatura) {
                // Debitar da conta e marcar como paga
                $conta->update([
                    'saldo_permuta' => max(0, $conta->saldo_permuta - $cobranca->valor_fatura),
                ]);

                $cobranca->update([
                    'status' => Cobranca::STATUS_PAGA
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Erro no pagamento automático da cobrança {$cobranca->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Executar limpezas e manutenções
     */
    private function executarLimpezas(): void
    {
        if (!$this->configuracao['executar_limpeza']) {
            return;
        }

        $this->info('');
        $this->info('🧹 EXECUTANDO LIMPEZAS E MANUTENÇÕES');
        $this->info('====================================');

        // 1. Limpar cobranças muito antigas canceladas
        $this->limparCobrancasAntigasCanceladas();

        // 2. Corrigir inconsistências de dados
        $this->corrigirInconsistencias();

        // 3. Atualizar estatísticas
        $this->atualizarEstatisticas();
    }

    /**
     * Limpar cobranças antigas canceladas
     */
    private function limparCobrancasAntigasCanceladas(): void
    {
        $this->info('🗑️ Limpando cobranças antigas canceladas...');

        $dataLimite = now()->subMonths(6); // Older than 6 months

        try {
            $query = Cobranca::where('status', Cobranca::STATUS_CANCELADA)
                ->where('created_at', '<', $dataLimite);

            $quantidade = $query->count();

            if (!$this->dryRun && $quantidade > 0) {
                $removidas = $query->delete();
            } else {
                $removidas = $quantidade;
            }

            $this->resultados['limpeza_canceladas'] = [
                'executado' => true,
                'sucesso' => true,
                'removidas' => $removidas,
            ];

            $this->info("✅ {$removidas} cobranças canceladas antigas removidas");

        } catch (\Exception $e) {
            $this->error("❌ Erro na limpeza: {$e->getMessage()}");

            $this->resultados['limpeza_canceladas'] = [
                'executado' => true,
                'sucesso' => false,
                'erro' => $e->getMessage(),
            ];
        }
    }

    /**
     * Corrigir inconsistências de dados
     */
    private function corrigirInconsistencias(): void
    {
        $this->info('🔧 Corrigindo inconsistências...');

        try {
            $correcoes = 0;

            // Corrigir status baseado na data de vencimento
            if (!$this->dryRun) {
                $correcoes = Cobranca::where('status', Cobranca::STATUS_PENDENTE)
                    ->where('vencimento_fatura', '<', now())
                    ->update(['status' => Cobranca::STATUS_VENCIDA]);
            } else {
                $correcoes = Cobranca::where('status', Cobranca::STATUS_PENDENTE)
                    ->where('vencimento_fatura', '<', now())
                    ->count();
            }

            $this->resultados['correcao_inconsistencias'] = [
                'executado' => true,
                'sucesso' => true,
                'correcoes' => $correcoes,
            ];

            $this->info("✅ {$correcoes} inconsistências corrigidas");

        } catch (\Exception $e) {
            $this->error("❌ Erro nas correções: {$e->getMessage()}");

            $this->resultados['correcao_inconsistencias'] = [
                'executado' => true,
                'sucesso' => false,
                'erro' => $e->getMessage(),
            ];
        }
    }

    /**
     * Atualizar estatísticas do sistema
     */
    private function atualizarEstatisticas(): void
    {
        $this->info('📊 Atualizando estatísticas...');

        try {
            $stats = [
                'total_cobrancas' => Cobranca::count(),
                'valor_total_pendente' => Cobranca::pendentes()->sum('valor_fatura'),
                'valor_total_vencido' => Cobranca::vencidas()->sum('valor_fatura'),
                'inadimplencia_percentual' => 0,
            ];

            $totalGeral = Cobranca::sum('valor_fatura');
            if ($totalGeral > 0) {
                $stats['inadimplencia_percentual'] = round(($stats['valor_total_vencido'] / $totalGeral) * 100, 2);
            }

            $this->resultados['estatisticas'] = $stats;

            $this->info("✅ Estatísticas atualizadas");

        } catch (\Exception $e) {
            $this->error("❌ Erro ao atualizar estatísticas: {$e->getMessage()}");
        }
    }

    /**
     * Gerar relatório final
     */
    private function gerarRelatorioFinal(float $inicio): void
    {
        $tempo = round(microtime(true) - $inicio, 2);

        $this->info('');
        $this->info('📊 RELATÓRIO FINAL DE EXECUÇÃO');
        $this->info('==============================');
        $this->info("⏱️ Tempo de execução: {$tempo} segundos");

        foreach ($this->resultados as $processo => $resultado) {
            $sucesso = $resultado['sucesso'] ?? true; // Default para true se não existir
            $status = $sucesso ? '✅' : '❌';
            $nome = str_replace('_', ' ', ucfirst($processo));

            $this->line("{$status} {$nome}");

            if (isset($resultado['erro'])) {
                $this->line("   Erro: {$resultado['erro']}");
            }
        }

        // Estatísticas finais se disponíveis
        if (isset($this->resultados['estatisticas'])) {
            $stats = $this->resultados['estatisticas'];
            $this->info('');
            $this->info('📈 ESTATÍSTICAS ATUAIS:');
            $this->info("   📄 Total de cobranças: {$stats['total_cobrancas']}");
            $this->info("   💰 Valor pendente: R$ " . number_format($stats['valor_total_pendente'], 2, ',', '.'));
            $this->info("   🔴 Valor vencido: R$ " . number_format($stats['valor_total_vencido'], 2, ',', '.'));
            $this->info("   📊 Inadimplência: {$stats['inadimplencia_percentual']}%");
        }

        if ($this->dryRun) {
            $this->warn('🔍 EXECUÇÃO EM MODO DRY RUN - Nenhuma alteração foi salva');
        } else {
            $this->info('✅ Processamento automático concluído com sucesso!');
        }

        // Log da execução
        Log::info('Processamento automático de cobranças executado', [
            'tempo_execucao' => $tempo,
            'resultados' => $this->resultados,
            'dry_run' => $this->dryRun,
        ]);
    }

    /**
     * Gerar apenas relatório sem processar
     */
    private function gerarRelatorioCompleto(): int
    {
        $this->info('📊 RELATÓRIO COMPLETO DO SISTEMA DE COBRANÇAS');
        $this->info('=============================================');

        $resumo = Cobranca::resumoFinanceiro();
        $totalPorStatus = Cobranca::totalPorStatus();

        $this->info('💰 RESUMO FINANCEIRO:');
        $this->info("   💳 Pendentes: R$ " . number_format($resumo['pendentes'], 2, ',', '.'));
        $this->info("   🔴 Vencidas: R$ " . number_format($resumo['vencidas'], 2, ',', '.'));
        $this->info("   ✅ Pagas: R$ " . number_format($resumo['pagas'], 2, ',', '.'));
        $this->info("   📊 Total: R$ " . number_format($resumo['total'], 2, ',', '.'));
        $this->info("   📈 Inadimplência: {$resumo['inadimplencia_percentual']}%");

        $this->info('');
        $this->info('📋 POR STATUS:');
        foreach (Cobranca::getStatusOptions() as $status => $label) {
            if (isset($totalPorStatus[$status])) {
                $qtd = $totalPorStatus[$status]['total'];
                $valor = number_format($totalPorStatus[$status]['valor_total'], 2, ',', '.');
                $this->info("   {$label}: {$qtd} (R$ {$valor})");
            }
        }

        return Command::SUCCESS;
    }
}
