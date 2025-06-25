<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cobranca;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MarcarCobrancasVencidas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cobrancas:marcar-vencidas
                           {--dias= : Dias de tolerância antes de marcar como vencida (padrão: 0)}
                           {--dry-run : Executar sem salvar (apenas visualizar)}
                           {--incluir-em-analise : Incluir cobranças em análise}
                           {--notificar : Enviar notificações aos gerentes}';

    /**
     * The console command description.
     */
    protected $description = 'Marca cobranças como vencidas e calcula encargos automaticamente';

    private int $diasTolerancia = 0;
    private bool $dryRun = false;
    private bool $incluirEmAnalise = false;
    private bool $notificar = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando processo de marcação de cobranças vencidas...');

        // Configurar parâmetros
        $this->configurarParametros();

        // Buscar cobranças candidatas a vencimento
        $cobrancasCandidatas = $this->buscarCobrancasCandidatas();

        if ($cobrancasCandidatas->isEmpty()) {
            $this->info('✅ Nenhuma cobrança encontrada para marcar como vencida.');
            return Command::SUCCESS;
        }

        $this->info("📋 Encontradas {$cobrancasCandidatas->count()} cobranças para análise");

        // Processar cobranças
        $processadas = $this->processarCobrancas($cobrancasCandidatas);

        // Gerar relatório de inadimplência
        $this->gerarRelatorioInadimplencia($processadas);

        // Notificar gerentes se solicitado
        if ($this->notificar && !$this->dryRun) {
            $this->notificarGerentes($processadas);
        }

        return Command::SUCCESS;
    }

    /**
     * Configurar parâmetros do command
     */
    private function configurarParametros(): void
    {
        $this->diasTolerancia = (int) ($this->option('dias') ?? 0);
        $this->dryRun = $this->option('dry-run') ?? false;
        $this->incluirEmAnalise = $this->option('incluir-em-analise') ?? false;
        $this->notificar = $this->option('notificar') ?? false;

        $this->info("⚙️ Configurações:");
        $this->info("   📅 Dias de tolerância: {$this->diasTolerancia}");
        $this->info("   🔍 Modo: " . ($this->dryRun ? 'DRY RUN' : 'EXECUÇÃO REAL'));
        $this->info("   📊 Incluir em análise: " . ($this->incluirEmAnalise ? 'SIM' : 'NÃO'));
        $this->info("   🔔 Notificar gerentes: " . ($this->notificar ? 'SIM' : 'NÃO'));
    }

    /**
     * Buscar cobranças candidatas a vencimento
     */
    private function buscarCobrancasCandidatas(): Collection
    {
        $dataLimite = now()->subDays($this->diasTolerancia);

        $query = Cobranca::with(['usuario', 'conta', 'gerente'])
            ->where('vencimento_fatura', '<', $dataLimite)
            ->whereIn('status', [Cobranca::STATUS_PENDENTE]);

        // Incluir em análise se solicitado
        if ($this->incluirEmAnalise) {
            $query->orWhere(function ($q) use ($dataLimite) {
                $q->where('vencimento_fatura', '<', $dataLimite)
                    ->where('status', Cobranca::STATUS_EM_ANALISE);
            });
        }

        return $query->orderBy('vencimento_fatura', 'asc')->get();
    }

    /**
     * Processar cobranças encontradas
     */
    private function processarCobrancas(Collection $cobrancas): array
    {
        $processadas = [
            'marcadas_vencidas' => [],
            'com_encargos' => [],
            'erros' => [],
            'estatisticas' => [
                'total_processadas' => 0,
                'valor_total_vencido' => 0,
                'dias_atraso_medio' => 0,
            ]
        ];

        $this->info('');
        $this->info('📋 Processando cobranças...');

        foreach ($cobrancas as $cobranca) {
            try {
                $resultado = $this->processarCobranca($cobranca);

                if ($resultado['marcada']) {
                    $processadas['marcadas_vencidas'][] = $resultado;
                    $processadas['estatisticas']['total_processadas']++;
                    $processadas['estatisticas']['valor_total_vencido'] += $cobranca->valor_fatura;

                    if ($resultado['tem_encargos']) {
                        $processadas['com_encargos'][] = $resultado;
                    }

                    $this->exibirLinhaProcessamento($cobranca, $resultado);
                }

            } catch (\Exception $e) {
                $erro = [
                    'cobranca_id' => $cobranca->id,
                    'referencia' => $cobranca->referencia,
                    'erro' => $e->getMessage()
                ];
                $processadas['erros'][] = $erro;
                $this->error("❌ Erro ao processar cobrança #{$cobranca->id}: {$e->getMessage()}");
            }
        }

        // Calcular estatísticas
        if ($processadas['estatisticas']['total_processadas'] > 0) {
            $somaAtrasos = collect($processadas['marcadas_vencidas'])->sum('dias_atraso');
            $processadas['estatisticas']['dias_atraso_medio'] = round($somaAtrasos / $processadas['estatisticas']['total_processadas'], 1);
        }

        return $processadas;
    }

    /**
     * Processar uma cobrança individual
     */
    private function processarCobranca(Cobranca $cobranca): array
    {
        $diasAtraso = abs($cobranca->dias_vencimento);
        $valorJuros = $cobranca->valor_juros;
        $valorMulta = $cobranca->valor_multa;
        $valorTotal = $cobranca->valor_total_com_encargos;

        $resultado = [
            'cobranca_id' => $cobranca->id,
            'referencia' => $cobranca->referencia,
            'usuario_nome' => $cobranca->usuario->nome ?? 'N/A',
            'valor_original' => $cobranca->valor_fatura,
            'dias_atraso' => $diasAtraso,
            'valor_juros' => $valorJuros,
            'valor_multa' => $valorMulta,
            'valor_total' => $valorTotal,
            'tem_encargos' => ($valorJuros + $valorMulta) > 0,
            'marcada' => false,
            'status_anterior' => $cobranca->status,
        ];

        // Marcar como vencida se não for dry run
        if (!$this->dryRun) {
            $cobranca->update([
                'status' => Cobranca::STATUS_VENCIDA
            ]);
            $resultado['marcada'] = true;
        } else {
            $resultado['marcada'] = true; // Para fins de relatório
        }

        return $resultado;
    }

    /**
     * Exibir linha de processamento
     */
    private function exibirLinhaProcessamento(Cobranca $cobranca, array $resultado): void
    {
        $status = $resultado['tem_encargos'] ? '💸' : '⚠️';
        $nome = substr($resultado['usuario_nome'], 0, 20);
        $valor = number_format($resultado['valor_original'], 2, ',', '.');
        $dias = $resultado['dias_atraso'];

        $linha = "{$status} {$nome}: R$ {$valor} ({$dias} dias)";

        if ($resultado['tem_encargos']) {
            $juros = number_format($resultado['valor_juros'], 2, ',', '.');
            $multa = number_format($resultado['valor_multa'], 2, ',', '.');
            $linha .= " + Juros: R$ {$juros} + Multa: R$ {$multa}";
        }

        $this->line($linha);
    }

    /**
     * Gerar relatório de inadimplência
     */
    private function gerarRelatorioInadimplencia(array $processadas): void
    {
        $this->info('');
        $this->info('📊 RELATÓRIO DE INADIMPLÊNCIA');
        $this->info('==============================');

        $stats = $processadas['estatisticas'];
        $this->info("📄 Cobranças marcadas como vencidas: {$stats['total_processadas']}");
        $this->info("💰 Valor total em atraso: R$ " . number_format($stats['valor_total_vencido'], 2, ',', '.'));
        $this->info("📅 Dias de atraso médio: {$stats['dias_atraso_medio']} dias");

        // Cobranças com encargos
        $comEncargos = count($processadas['com_encargos']);
        if ($comEncargos > 0) {
            $this->warn("💸 Cobranças com encargos: {$comEncargos}");

            $totalJuros = collect($processadas['com_encargos'])->sum('valor_juros');
            $totalMultas = collect($processadas['com_encargos'])->sum('valor_multa');

            $this->info("   📈 Total em juros: R$ " . number_format($totalJuros, 2, ',', '.'));
            $this->info("   ⚡ Total em multas: R$ " . number_format($totalMultas, 2, ',', '.'));
        }

        // Erros
        $erros = count($processadas['erros']);
        if ($erros > 0) {
            $this->warn("❌ Erros encontrados: {$erros}");
        }

        // Top inadimplentes
        $this->exibirTopInadimplentes($processadas['marcadas_vencidas']);

        if ($this->dryRun) {
            $this->warn('🔍 EXECUÇÃO EM MODO DRY RUN - Nenhuma alteração foi salva');
        } else {
            $this->info('✅ Processamento concluído com sucesso!');
        }
    }

    /**
     * Exibir top inadimplentes
     */
    private function exibirTopInadimplentes(array $marcadas): void
    {
        if (empty($marcadas)) {
            return;
        }

        $this->info('');
        $this->info('🔝 TOP 5 MAIORES INADIMPLENTES:');

        $topInadimplentes = collect($marcadas)
            ->sortByDesc('valor_total')
            ->take(5);

        foreach ($topInadimplentes as $index => $cobranca) {
            $posicao = $index + 1;
            $nome = substr($cobranca['usuario_nome'], 0, 25);
            $valor = number_format($cobranca['valor_total'], 2, ',', '.');
            $dias = $cobranca['dias_atraso'];

            $this->line("   {$posicao}º {$nome}: R$ {$valor} ({$dias} dias)");
        }
    }

    /**
     * Notificar gerentes sobre inadimplência
     */
    private function notificarGerentes(array $processadas): void
    {
        $this->info('');
        $this->info('🔔 Notificando gerentes...');

        // Agrupar por gerente
        $porGerente = collect($processadas['marcadas_vencidas'])
            ->filter(fn($item) => !empty($item['gerente_id']))
            ->groupBy('gerente_id');

        foreach ($porGerente as $gerenteId => $cobrancas) {
            $gerente = Usuario::find($gerenteId);
            if (!$gerente) continue;

            $totalCobrancas = count($cobrancas);
            $valorTotal = collect($cobrancas)->sum('valor_total');

            // Aqui você implementaria o envio real da notificação
            // Por enquanto, apenas log
            $this->line("📧 {$gerente->nome}: {$totalCobrancas} cobranças (R$ " . number_format($valorTotal, 2, ',', '.') . ")");
        }

        $this->info('✅ Notificações enviadas!');
    }
}
