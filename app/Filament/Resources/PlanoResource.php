<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanoResource\Pages;
use App\Filament\Resources\PlanoResource\RelationManagers;
use App\Models\Plano;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PlanoResource extends Resource
{
    protected static ?string $model = Plano::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Planos';

    protected static ?string $modelLabel = 'Plano';

    protected static ?string $pluralModelLabel = 'Planos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Plano')
                    ->description('Configure os dados principais do plano')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nome_plano')
                                    ->label('Nome do Plano')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('Ex: Básico, Premium, Empresarial')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('tipo_do_plano')
                                    ->label('Tipo do Plano')
                                    ->options([
                                        'mensal' => 'Mensal',
                                        'trimestral' => 'Trimestral',
                                        'semestral' => 'Semestral',
                                        'anual' => 'Anual',
                                        'vitalicio' => 'Vitalício',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\FileUpload::make('imagem')
                            ->label('Imagem do Plano')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                            ->directory('planos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Imagem representativa do plano (máximo 2MB)')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Configuração de Taxas')
                    ->description('Defina as taxas e valores do plano')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('taxa_inscricao')
                                    ->label('Taxa de Inscrição')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->helperText('Valor único pago na adesão')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('taxa_comissao')
                                    ->label('Taxa de Comissão')
                                    ->numeric()
                                    ->suffix('%')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->helperText('Percentual sobre transações')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('taxa_manutencao_anual')
                                    ->label('Taxa de Manutenção Anual')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->helperText('Valor anual de manutenção')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Simulador Financeiro')
                    ->description('Simule o custo total do plano')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor_transacoes_sim')
                                    ->label('Valor Mensal em Transações')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->step(0.01)
                                    ->live()
                                    ->helperText('Para simulação de custos')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('custo_mensal_sim')
                                    ->label('Custo Mensal Estimado')
                                    ->content(function ($get) {
                                        $valorTransacoes = $get('valor_transacoes_sim');
                                        $taxaComissao = $get('taxa_comissao');
                                        $taxaManutencao = $get('taxa_manutencao_anual');

                                        if ($valorTransacoes && $taxaComissao && $taxaManutencao) {
                                            $comissaoMensal = ($valorTransacoes * $taxaComissao) / 100;
                                            $manutencaoMensal = $taxaManutencao / 12;
                                            $total = $comissaoMensal + $manutencaoMensal;

                                            return 'R$ ' . number_format($total, 2, ',', '.');
                                        }

                                        return 'Configure os valores para simular';
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('custo_anual_sim')
                                    ->label('Custo Anual Estimado')
                                    ->content(function ($get) {
                                        $valorTransacoes = $get('valor_transacoes_sim');
                                        $taxaComissao = $get('taxa_comissao');
                                        $taxaManutencao = $get('taxa_manutencao_anual');
                                        $taxaInscricao = $get('taxa_inscricao');

                                        if ($valorTransacoes && $taxaComissao && $taxaManutencao && $taxaInscricao) {
                                            $comissaoAnual = ($valorTransacoes * 12 * $taxaComissao) / 100;
                                            $total = $comissaoAnual + $taxaManutencao + $taxaInscricao;

                                            return 'R$ ' . number_format($total, 2, ',', '.');
                                        }

                                        return 'Configure os valores para simular';
                                    })
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagem')
                    ->label('Imagem')
                    ->circular()
                    ->size(50)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Plano&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('nome_plano')
                    ->label('Nome do Plano')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_do_plano')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mensal' => 'info',
                        'trimestral' => 'warning',
                        'semestral' => 'success',
                        'anual' => 'primary',
                        'vitalicio' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('taxa_inscricao')
                    ->label('Taxa Inscrição')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('taxa_comissao')
                    ->label('% Comissão')
                    ->suffix('%')
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('taxa_manutencao_anual')
                    ->label('Manutenção Anual')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('custo_mensal_estimado')
                    ->label('Custo Mensal*')
                    ->getStateUsing(function ($record) {
                        // Simulação com R$ 10.000 em transações mensais
                        $valorTransacoes = 10000;
                        $comissaoMensal = ($valorTransacoes * $record->taxa_comissao) / 100;
                        $manutencaoMensal = $record->taxa_manutencao_anual / 12;
                        $total = $comissaoMensal + $manutencaoMensal;

                        return 'R$ ' . number_format($total, 2, ',', '.');
                    })
                    ->color('info')
                    ->helperText('*Base: R$ 10k transações/mês'),

                Tables\Columns\TextColumn::make('contas_count')
                    ->label('Contas Ativas')
                    ->counts('contas')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nome_plano', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_do_plano')
                    ->label('Tipo do Plano')
                    ->options([
                        'mensal' => 'Mensal',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                        'vitalicio' => 'Vitalício',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('taxa_comissao_range')
                    ->form([
                        Forms\Components\TextInput::make('comissao_min')
                            ->label('Comissão Mínima (%)')
                            ->numeric(),
                        Forms\Components\TextInput::make('comissao_max')
                            ->label('Comissão Máxima (%)')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['comissao_min'],
                                fn (Builder $query, $valor): Builder => $query->where('taxa_comissao', '>=', $valor),
                            )
                            ->when(
                                $data['comissao_max'],
                                fn (Builder $query, $valor): Builder => $query->where('taxa_comissao', '<=', $valor),
                            );
                    }),

                Tables\Filters\Filter::make('com_contas')
                    ->label('Com Contas Ativas')
                    ->query(fn (Builder $query): Builder => $query->has('contas')),

                Tables\Filters\Filter::make('sem_contas')
                    ->label('Sem Contas')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('contas')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('novo_nome')
                            ->label('Nome do Novo Plano')
                            ->required()
                            ->placeholder('Ex: Premium Plus'),
                    ])
                    ->action(function (Plano $record, array $data) {
                        $novoPlano = $record->replicate();
                        $novoPlano->nome_plano = $data['novo_nome'];
                        $novoPlano->save();

                        Notification::make()
                            ->title('Plano duplicado!')
                            ->body("Novo plano '{$data['novo_nome']}' criado com base em '{$record->nome_plano}'.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('simular_receita')
                    ->label('Simular Receita')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('numero_clientes')
                            ->label('Número de Clientes')
                            ->numeric()
                            ->required()
                            ->default(100),

                        Forms\Components\TextInput::make('transacao_media_mensal')
                            ->label('Transação Média Mensal (R$)')
                            ->numeric()
                            ->required()
                            ->default(5000),
                    ])
                    ->action(function (Plano $record, array $data) {
                        $clientes = $data['numero_clientes'];
                        $transacaoMedia = $data['transacao_media_mensal'];

                        $receitaInscricao = $clientes * $record->taxa_inscricao;
                        $receitaComissaoMensal = $clientes * (($transacaoMedia * $record->taxa_comissao) / 100);
                        $receitaManutencaoAnual = $clientes * $record->taxa_manutencao_anual;
                        $receitaAnualTotal = $receitaInscricao + ($receitaComissaoMensal * 12) + $receitaManutencaoAnual;

                        Notification::make()
                            ->title('Simulação de Receita')
                            ->body("
                                Receita Anual Estimada: R$ " . number_format($receitaAnualTotal, 2, ',', '.') . "
                                \nInscrições: R$ " . number_format($receitaInscricao, 2, ',', '.') . "
                                \nComissões: R$ " . number_format($receitaComissaoMensal * 12, 2, ',', '.') . "
                                \nManutenção: R$ " . number_format($receitaManutencaoAnual, 2, ',', '.')
                            )
                            ->info()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('ajustar_comissao')
                        ->label('Ajustar Comissão')
                        ->icon('heroicon-o-percent-badge')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('tipo_ajuste')
                                ->label('Tipo de Ajuste')
                                ->options([
                                    'aumentar' => 'Aumentar',
                                    'diminuir' => 'Diminuir',
                                    'definir' => 'Definir Valor',
                                ])
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('valor_ajuste')
                                ->label(fn ($get) => $get('tipo_ajuste') === 'definir' ? 'Nova Comissão (%)' : 'Valor do Ajuste (%)')
                                ->numeric()
                                ->required()
                                ->step(0.01),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            $count = 0;

                            foreach ($records as $record) {
                                $comissaoAtual = $record->taxa_comissao;

                                switch ($data['tipo_ajuste']) {
                                    case 'aumentar':
                                        $novaComissao = $comissaoAtual + $data['valor_ajuste'];
                                        break;
                                    case 'diminuir':
                                        $novaComissao = max(0, $comissaoAtual - $data['valor_ajuste']);
                                        break;
                                    case 'definir':
                                        $novaComissao = $data['valor_ajuste'];
                                        break;
                                    default:
                                        $novaComissao = $comissaoAtual;
                                }

                                $record->update(['taxa_comissao' => min(100, $novaComissao)]);
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} planos ajustados!")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('comparar_planos')
                        ->label('Comparar Planos')
                        ->icon('heroicon-o-scale')
                        ->color('info')
                        ->action(function ($records) {
                            $comparacao = [];

                            foreach ($records as $record) {
                                // Simulação com R$ 10.000 em transações mensais
                                $valorTransacoes = 10000;
                                $custoMensal = (($valorTransacoes * $record->taxa_comissao) / 100) + ($record->taxa_manutencao_anual / 12);

                                $comparacao[] = "{$record->nome_plano}: R$ " . number_format($custoMensal, 2, ',', '.') . "/mês";
                            }

                            Notification::make()
                                ->title('Comparação de Custos Mensais')
                                ->body(implode("\n", $comparacao))
                                ->info()
                                ->persistent()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhum plano encontrado')
            ->emptyStateDescription('Comece criando planos para oferecer aos seus clientes.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanos::route('/'),
            'create' => Pages\CreatePlano::route('/create'),
            'view' => Pages\ViewPlano::route('/{record}'),
            'edit' => Pages\EditPlano::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::count();
        return $count > 0 ? 'primary' : 'gray';
    }
}
