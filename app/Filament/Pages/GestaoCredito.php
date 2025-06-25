<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use App\Models\SolicitacaoCredito;
use App\Models\Conta;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

class GestaoCredito extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Gestão de Crédito';

    protected static ?string $title = 'Gestão de Crédito';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.gestao-credito';

    // Propriedades para widgets
    public $totalSolicitacoes;
    public $pendentesAprovacao;
    public $valorTotalSolicitado;
    public $taxaAprovacao;
    public $limiteTotalDisponivel;
    public $limiteUtilizado;

    public function mount(): void
    {
        $this->atualizarEstatisticas();
    }

    private function atualizarEstatisticas(): void
    {
        $this->totalSolicitacoes = SolicitacaoCredito::count();
        $this->pendentesAprovacao = SolicitacaoCredito::whereIn('status', ['Pendente', 'Em Análise'])->count();
        $this->valorTotalSolicitado = SolicitacaoCredito::sum('valor_solicitado');

        $totalAnalisadas = SolicitacaoCredito::whereIn('status', ['Aprovado', 'Negado'])->count();
        $aprovadas = SolicitacaoCredito::where('status', 'Aprovado')->count();
        $this->taxaAprovacao = $totalAnalisadas > 0 ? round(($aprovadas / $totalAnalisadas) * 100, 1) : 0;

        $this->limiteTotalDisponivel = Conta::sum('limite_credito');
        $this->limiteUtilizado = Conta::sum('limite_utilizado');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SolicitacaoCredito::query())
            ->heading('Solicitações de Crédito Recentes')
            ->description('Acompanhe as solicitações que precisam de atenção')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuarioSolicitante.nome')
                    ->label('Solicitante')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('valor_solicitado')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendente' => 'warning',
                        'Em Análise' => 'info',
                        'Aprovado' => 'success',
                        'Negado' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('matriz.nome')
                    ->label('Responsável')
                    ->limit(20)
                    ->placeholder('Não definido'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('urgencia')
                    ->label('Urgência')
                    ->getStateUsing(function ($record) {
                        $dias = now()->diffInDays($record->created_at);

                        if ($record->status !== 'Pendente') {
                            return 'Processado';
                        }

                        if ($dias >= 7) {
                            return 'Alta';
                        } elseif ($dias >= 3) {
                            return 'Média';
                        } else {
                            return 'Baixa';
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Alta' => 'danger',
                        'Média' => 'warning',
                        'Baixa' => 'success',
                        'Processado' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pendente' => 'Pendente',
                        'Em Análise' => 'Em Análise',
                        'Aprovado' => 'Aprovado',
                        'Negado' => 'Negado',
                    ])
                    ->default('Pendente'),

                Tables\Filters\Filter::make('urgentes')
                    ->label('Urgentes (>7 dias)')
                    ->query(fn (Builder $query): Builder =>
                    $query->where('created_at', '<', now()->subDays(7))
                        ->where('status', 'Pendente')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comentario')
                            ->label('Comentário da Aprovação')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SolicitacaoCredito $record, array $data) {
                        $record->update([
                            'status' => 'Aprovado',
                            'matriz_aprovacao' => true,
                            'comentario_matriz' => $data['comentario'],
                        ]);

                        $this->atualizarEstatisticas();

                        Notification::make()
                            ->title('Crédito aprovado!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => in_array($record->status, ['Pendente', 'Em Análise'])),

                Tables\Actions\Action::make('negar')
                    ->label('Negar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo da Negação')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SolicitacaoCredito $record, array $data) {
                        $record->update([
                            'status' => 'Negado',
                            'matriz_aprovacao' => false,
                            'motivo_rejeicao' => $data['motivo'],
                        ]);

                        $this->atualizarEstatisticas();

                        Notification::make()
                            ->title('Crédito negado!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => in_array($record->status, ['Pendente', 'Em Análise'])),
            ])
            ->headerActions([
                Tables\Actions\Action::make('atualizar')
                    ->label('Atualizar Dados')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function () {
                        $this->atualizarEstatisticas();

                        Notification::make()
                            ->title('Dados atualizados!')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('relatorio_credito')
                    ->label('Gerar Relatório')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->action(function () {
                        $relatorio = $this->gerarRelatorioCredito();

                        Notification::make()
                            ->title('Relatório de Crédito')
                            ->body($relatorio)
                            ->info()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Nenhuma solicitação encontrada')
            ->emptyStateDescription('Todas as solicitações estão processadas ou não há solicitações no momento.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
    }

    private function gerarRelatorioCredito(): string
    {
        $hoje = now();
        $semanaPassada = now()->subWeek();
        $mesPassado = now()->subMonth();

        $solicitacoesSemanais = SolicitacaoCredito::where('created_at', '>=', $semanaPassada)->count();
        $solicitacoesMensais = SolicitacaoCredito::where('created_at', '>=', $mesPassado)->count();
        $valorAprovadoMes = SolicitacaoCredito::where('status', 'Aprovado')
            ->where('created_at', '>=', $mesPassado)
            ->sum('valor_solicitado');
        $valorNegadoMes = SolicitacaoCredito::where('status', 'Negado')
            ->where('created_at', '>=', $mesPassado)
            ->sum('valor_solicitado');

        return "
            📊 RELATÓRIO DE CRÉDITO - " . $hoje->format('d/m/Y H:i') . "

            📈 Solicitações:
            • Esta semana: {$solicitacoesSemanais}
            • Este mês: {$solicitacoesMensais}
            • Total pendente: {$this->pendentesAprovacao}

            💰 Valores (Mês):
            • Aprovado: R$ " . number_format($valorAprovadoMes, 2, ',', '.') . "
            • Negado: R$ " . number_format($valorNegadoMes, 2, ',', '.') . "

            📊 Capacidade:
            • Limite total: R$ " . number_format($this->limiteTotalDisponivel, 2, ',', '.') . "
            • Utilizado: R$ " . number_format($this->limiteUtilizado, 2, ',', '.') . "
            • Disponível: R$ " . number_format($this->limiteTotalDisponivel - $this->limiteUtilizado, 2, ',', '.') . "

            ⚡ Taxa de Aprovação: {$this->taxaAprovacao}%
        ";
    }

    // Actions para widgets
    public function aprovarTodas()
    {
        $pendentes = SolicitacaoCredito::whereIn('status', ['Pendente', 'Em Análise'])->get();

        foreach ($pendentes as $solicitacao) {
            $solicitacao->update([
                'status' => 'Aprovado',
                'matriz_aprovacao' => true,
                'comentario_matriz' => 'Aprovação em lote via Gestão de Crédito',
            ]);
        }

        $this->atualizarEstatisticas();

        Notification::make()
            ->title('Todas as solicitações pendentes foram aprovadas!')
            ->success()
            ->send();
    }

    public function analisarUrgentes()
    {
        $urgentes = SolicitacaoCredito::where('created_at', '<', now()->subDays(7))
            ->where('status', 'Pendente')
            ->get();

        foreach ($urgentes as $solicitacao) {
            $solicitacao->update(['status' => 'Em Análise']);
        }

        $this->atualizarEstatisticas();

        Notification::make()
            ->title($urgentes->count() . ' solicitações marcadas como "Em Análise"')
            ->success()
            ->send();
    }
}
