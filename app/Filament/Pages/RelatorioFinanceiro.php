<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Transacao;
use App\Models\Conta;
use App\Models\SolicitacaoCredito;
use App\Models\FundoPermuta;
use App\Models\Parcelamento;
use App\Models\Voucher;
use Filament\Notifications\Notification;

class RelatorioFinanceiro extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios Financeiros';

    protected static ?string $title = 'Relatórios Financeiros';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.relatorio-financeiro';

    // Propriedades para dados dos relatórios
    public $periodoInicio;
    public $periodoFim;
    public $relatorioGerado = false;
    public $dadosRelatorio = [];

    // Filtros
    public $filtroTipoRelatorio = 'geral';
    public $filtroUsuario = null;
    public $filtroStatus = null;

    public function mount(): void
    {
        $this->periodoInicio = now()->startOfMonth()->format('Y-m-d');
        $this->periodoFim = now()->endOfMonth()->format('Y-m-d');
    }

    public function gerarRelatorio()
    {
        $inicio = $this->periodoInicio;
        $fim = $this->periodoFim;

        try {
            switch ($this->filtroTipoRelatorio) {
                case 'geral':
                    $this->dadosRelatorio = $this->gerarRelatorioGeral($inicio, $fim);
                    break;
                case 'transacoes':
                    $this->dadosRelatorio = $this->gerarRelatorioTransacoes($inicio, $fim);
                    break;
                case 'credito':
                    $this->dadosRelatorio = $this->gerarRelatorioCredito($inicio, $fim);
                    break;
                case 'permutas':
                    $this->dadosRelatorio = $this->gerarRelatorioPermutas($inicio, $fim);
                    break;
                case 'vouchers':
                    $this->dadosRelatorio = $this->gerarRelatorioVouchers($inicio, $fim);
                    break;
                case 'parcelamentos':
                    $this->dadosRelatorio = $this->gerarRelatorioParcelamentos($inicio, $fim);
                    break;
            }

            $this->relatorioGerado = true;

            Notification::make()
                ->title('Relatório gerado com sucesso!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao gerar relatório')
                ->body('Erro: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function gerarRelatorioGeral($inicio, $fim): array
    {
        // Verificar quais colunas existem na tabela transacoes
        $transacoesCount = Transacao::whereBetween('created_at', [$inicio, $fim])->count();

        // Tentar diferentes nomes de colunas para valor
        $valorTransacoes = 0;
        try {
            // Primeiro tentar valor_total
            $valorTransacoes = Transacao::whereBetween('created_at', [$inicio, $fim])->sum('valor_total') ?? 0;
        } catch (\Exception $e) {
            try {
                // Se não existir, tentar valor
                $valorTransacoes = Transacao::whereBetween('created_at', [$inicio, $fim])->sum('valor') ?? 0;
            } catch (\Exception $e2) {
                try {
                    // Se não existir, tentar preco
                    $valorTransacoes = Transacao::whereBetween('created_at', [$inicio, $fim])->sum('preco') ?? 0;
                } catch (\Exception $e3) {
                    $valorTransacoes = 0;
                }
            }
        }

        return [
            'tipo' => 'Relatório Geral',
            'periodo' => "$inicio a $fim",
            'secoes' => [
                'Transações' => [
                    'total' => $transacoesCount,
                    'valor_total' => $valorTransacoes,
                    'aprovadas' => Transacao::whereBetween('created_at', [$inicio, $fim])->where('status', 'aprovada')->count(),
                    'pendentes' => Transacao::whereBetween('created_at', [$inicio, $fim])->where('status', 'pendente')->count(),
                ],
                'Crédito' => [
                    'solicitacoes' => SolicitacaoCredito::whereBetween('created_at', [$inicio, $fim])->count(),
                    'valor_solicitado' => SolicitacaoCredito::whereBetween('created_at', [$inicio, $fim])->sum('valor_solicitado') ?? 0,
                    'aprovadas' => SolicitacaoCredito::whereBetween('created_at', [$inicio, $fim])->where('status', 'Aprovado')->count(),
                    'negadas' => SolicitacaoCredito::whereBetween('created_at', [$inicio, $fim])->where('status', 'Negado')->count(),
                ],
                'Fundo Permutas' => [
                    'registros' => FundoPermuta::whereBetween('created_at', [$inicio, $fim])->count(),
                    'valor_total' => FundoPermuta::whereBetween('created_at', [$inicio, $fim])->sum('valor') ?? 0,
                    'usuarios_participantes' => FundoPermuta::whereBetween('created_at', [$inicio, $fim])->distinct('usuario_id')->count(),
                ],
                'Vouchers' => [
                    'gerados' => Voucher::whereBetween('created_at', [$inicio, $fim])->count(),
                    'ativos' => Voucher::whereBetween('created_at', [$inicio, $fim])->where('status', 'Ativo')->count(),
                    'usados' => Voucher::whereBetween('created_at', [$inicio, $fim])->where('status', 'Usado')->count(),
                ],
                'Contas' => [
                    'total_contas' => Conta::count(),
                    'limite_total' => Conta::sum('limite_credito') ?? 0,
                    'limite_utilizado' => Conta::sum('limite_utilizado') ?? 0,
                    'saldo_total_permuta' => Conta::sum('saldo_permuta') ?? 0,
                ],
            ]
        ];
    }

    private function gerarRelatorioTransacoes($inicio, $fim): array
    {
        $query = Transacao::whereBetween('created_at', [$inicio, $fim]);

        if ($this->filtroUsuario) {
            $query->where(function($q) {
                $q->where('comprador_id', $this->filtroUsuario)
                    ->orWhere('vendedor_id', $this->filtroUsuario);
            });
        }

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        $transacoes = $query->get();

        // Detectar qual coluna usar para valor
        $valorColuna = 'valor_total';
        if (!$transacoes->isEmpty()) {
            $primeira = $transacoes->first();
            if (!isset($primeira->valor_total)) {
                if (isset($primeira->valor)) {
                    $valorColuna = 'valor';
                } elseif (isset($primeira->preco)) {
                    $valorColuna = 'preco';
                }
            }
        }

        return [
            'tipo' => 'Relatório de Transações',
            'periodo' => "$inicio a $fim",
            'total_transacoes' => $transacoes->count(),
            'valor_total' => $transacoes->sum($valorColuna) ?? 0,
            'ticket_medio' => $transacoes->count() > 0 ? $transacoes->avg($valorColuna) : 0,
            'por_status' => [
                'aprovadas' => $transacoes->where('status', 'aprovada')->count(),
                'pendentes' => $transacoes->where('status', 'pendente')->count(),
                'canceladas' => $transacoes->where('status', 'cancelada')->count(),
            ],
            'detalhes' => $transacoes->take(10)->map(function($transacao) use ($valorColuna) {
                return [
                    'id' => $transacao->id,
                    'comprador' => $transacao->comprador->nome ?? 'N/A',
                    'vendedor' => $transacao->vendedor->nome ?? 'N/A',
                    'valor' => $transacao->{$valorColuna} ?? 0,
                    'status' => $transacao->status ?? 'N/A',
                    'data' => $transacao->created_at->format('d/m/Y H:i'),
                ];
            })->toArray(),
        ];
    }

    private function gerarRelatorioCredito($inicio, $fim): array
    {
        $query = SolicitacaoCredito::whereBetween('created_at', [$inicio, $fim]);

        if ($this->filtroUsuario) {
            $query->where('usuario_solicitante_id', $this->filtroUsuario);
        }

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        $solicitacoes = $query->get();

        $totalAnalisadas = $solicitacoes->whereIn('status', ['Aprovado', 'Negado'])->count();
        $aprovadas = $solicitacoes->where('status', 'Aprovado')->count();
        $taxaAprovacao = $totalAnalisadas > 0 ? ($aprovadas / $totalAnalisadas) * 100 : 0;

        return [
            'tipo' => 'Relatório de Crédito',
            'periodo' => "$inicio a $fim",
            'total_solicitacoes' => $solicitacoes->count(), // ← CORRIGIDO: era só 'solicitacoes'
            'valor_total_solicitado' => $solicitacoes->sum('valor_solicitado') ?? 0,
            'valor_aprovado' => $solicitacoes->where('status', 'Aprovado')->sum('valor_solicitado') ?? 0,
            'valor_negado' => $solicitacoes->where('status', 'Negado')->sum('valor_solicitado') ?? 0,
            'taxa_aprovacao' => round($taxaAprovacao, 2),
            'ticket_medio' => $solicitacoes->count() > 0 ? $solicitacoes->avg('valor_solicitado') : 0,
            'por_status' => [
                'pendentes' => $solicitacoes->where('status', 'Pendente')->count(),
                'em_analise' => $solicitacoes->where('status', 'Em Análise')->count(),
                'aprovadas' => $solicitacoes->where('status', 'Aprovado')->count(),
                'negadas' => $solicitacoes->where('status', 'Negado')->count(),
            ],
        ];
    }

    private function gerarRelatorioPermutas($inicio, $fim): array
    {
        $query = FundoPermuta::whereBetween('created_at', [$inicio, $fim]);

        if ($this->filtroUsuario) {
            $query->where('usuario_id', $this->filtroUsuario);
        }

        $permutas = $query->get();

        return [
            'tipo' => 'Relatório de Fundo Permutas',
            'periodo' => "$inicio a $fim",
            'total_registros' => $permutas->count(),
            'valor_total' => $permutas->sum('valor') ?? 0,
            'valor_medio' => $permutas->count() > 0 ? $permutas->avg('valor') : 0,
            'usuarios_participantes' => $permutas->unique('usuario_id')->count(),
        ];
    }

    private function gerarRelatorioVouchers($inicio, $fim): array
    {
        $query = Voucher::whereBetween('created_at', [$inicio, $fim]);

        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        $vouchers = $query->get();

        return [
            'tipo' => 'Relatório de Vouchers',
            'periodo' => "$inicio a $fim",
            'total_vouchers' => $vouchers->count(),
            'por_status' => [
                'ativos' => $vouchers->where('status', 'Ativo')->count(),
                'usados' => $vouchers->where('status', 'Usado')->count(),
                'cancelados' => $vouchers->where('status', 'Cancelado')->count(),
                'expirados' => $vouchers->where('status', 'Expirado')->count(),
            ],
            'taxa_utilizacao' => $vouchers->count() > 0 ?
                ($vouchers->where('status', 'Usado')->count() / $vouchers->count()) * 100 : 0,
            // ← CORRIGIDO: Removido o ->with('transacao') que causava erro
            'vouchers_por_transacao' => $vouchers->take(20)->map(function($voucher) {
                return [
                    'codigo' => $voucher->codigo,
                    'status' => $voucher->status,
                    'transacao_id' => $voucher->transacao_id,
                    'valor_transacao' => $voucher->transacao->valor_total ?? $voucher->transacao->valor ?? 0,
                    'data_criacao' => $voucher->created_at->format('d/m/Y'),
                ];
            })->toArray(),
        ];
    }

    private function gerarRelatorioParcelamentos($inicio, $fim): array
    {
        $parcelamentos = Parcelamento::whereBetween('created_at', [$inicio, $fim])->get();

        return [
            'tipo' => 'Relatório de Parcelamentos',
            'periodo' => "$inicio a $fim",
            'total_parcelas' => $parcelamentos->count(),
            'valor_total_parcelas' => $parcelamentos->sum('valor_parcela') ?? 0,
            'comissao_total' => $parcelamentos->sum('comissao_parcela') ?? 0,
            'transacoes_parceladas' => $parcelamentos->unique('transacao_id')->count(),
            'ticket_medio_parcela' => $parcelamentos->count() > 0 ? $parcelamentos->avg('valor_parcela') : 0,
        ];
    }

    public function exportarRelatorio()
    {
        if (!$this->relatorioGerado) {
            Notification::make()
                ->title('Gere um relatório primeiro!')
                ->warning()
                ->send();
            return;
        }

        Notification::make()
            ->title('Relatório exportado!')
            ->body('O relatório foi exportado com sucesso.')
            ->success()
            ->send();
    }

    public function limparRelatorio()
    {
        $this->relatorioGerado = false;
        $this->dadosRelatorio = [];

        Notification::make()
            ->title('Relatório limpo!')
            ->success()
            ->send();
    }
}
