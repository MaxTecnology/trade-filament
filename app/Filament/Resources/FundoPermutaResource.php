<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundoPermutaResource\Pages;
use App\Filament\Resources\FundoPermutaResource\RelationManagers;
use App\Models\FundoPermuta;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class FundoPermutaResource extends Resource
{
    protected static ?string $model = FundoPermuta::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Fundo Permutas';

    protected static ?string $modelLabel = 'Fundo Permuta';

    protected static ?string $pluralModelLabel = 'Fundo Permutas';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Fundo Permuta')
                    ->description('Registre valores de permuta no sistema')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('usuario_id')
                                    ->label('Usuário')
                                    ->relationship('usuario', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Selecione o usuário para registrar o valor de permuta')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor da Permuta')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(999999.99)
                                    ->helperText('Valor em reais disponível para permuta')
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Calculadora de Permuta')
                    ->description('Ferramenta para calcular valores de permuta')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor_original_calc')
                                    ->label('Valor Original')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->step(0.01)
                                    ->live()
                                    ->helperText('Valor original da transação')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('percentual_permuta_calc')
                                    ->label('% Permuta')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->live()
                                    ->default(80)
                                    ->helperText('Percentual convertido em permuta')
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('valor_permuta_calc')
                                    ->label('Valor Calculado')
                                    ->content(function ($get) {
                                        $valorOriginal = $get('valor_original_calc');
                                        $percentual = $get('percentual_permuta_calc');

                                        if ($valorOriginal && $percentual) {
                                            $valorPermuta = ($valorOriginal * $percentual) / 100;
                                            return 'R$ ' . number_format($valorPermuta, 2, ',', '.');
                                        }

                                        return 'Configure os valores acima';
                                    })
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('aplicar_calculo')
                                ->label('Aplicar Cálculo')
                                ->icon('heroicon-o-calculator')
                                ->color('primary')
                                ->action(function ($set, $get) {
                                    $valorOriginal = $get('valor_original_calc');
                                    $percentual = $get('percentual_permuta_calc');

                                    if ($valorOriginal && $percentual) {
                                        $valorPermuta = ($valorOriginal * $percentual) / 100;
                                        $set('valor', $valorPermuta);
                                    }
                                }),
                        ])
                            ->fullWidth(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Forms\Components\Section::make('Resumo do Usuário')
                    ->description('Informações financeiras do usuário selecionado')
                    ->schema([
                        Forms\Components\Placeholder::make('resumo_usuario')
                            ->label('Dados Financeiros')
                            ->content(function ($get) {
                                $usuarioId = $get('usuario_id');

                                if (!$usuarioId) {
                                    return 'Selecione um usuário para ver o resumo';
                                }

                                $usuario = Usuario::find($usuarioId);
                                if (!$usuario) {
                                    return 'Usuário não encontrado';
                                }

                                $totalPermutas = FundoPermuta::where('usuario_id', $usuarioId)->sum('valor');
                                $quantidadePermutas = FundoPermuta::where('usuario_id', $usuarioId)->count();

                                $html = "<div class='space-y-2'>";
                                $html .= "<p><strong>Nome:</strong> {$usuario->nome}</p>";
                                $html .= "<p><strong>Email:</strong> {$usuario->email}</p>";
                                $html .= "<p><strong>Total em Permutas:</strong> R$ " . number_format($totalPermutas, 2, ',', '.') . "</p>";
                                $html .= "<p><strong>Quantidade de Registros:</strong> {$quantidadePermutas}</p>";
                                $html .= "</div>";

                                return $html;
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Usuário')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('usuario.email')
                    ->label('Email')
                    ->searchable()
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor Permuta')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                Tables\Columns\TextColumn::make('valor_formatado')
                    ->label('Classificação')
                    ->getStateUsing(function ($record) {
                        $valor = $record->valor;

                        if ($valor >= 10000) {
                            return 'Alto Valor';
                        } elseif ($valor >= 5000) {
                            return 'Médio Valor';
                        } elseif ($valor >= 1000) {
                            return 'Baixo Valor';
                        } else {
                            return 'Mínimo';
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Alto Valor' => 'success',
                        'Médio Valor' => 'warning',
                        'Baixo Valor' => 'info',
                        'Mínimo' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('percentual_total')
                    ->label('% do Total')
                    ->getStateUsing(function ($record) {
                        $totalGeral = FundoPermuta::sum('valor');
                        if ($totalGeral > 0) {
                            $percentual = ($record->valor / $totalGeral) * 100;
                            return number_format($percentual, 2) . '%';
                        }
                        return '0%';
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('usuario_total_permutas')
                    ->label('Total do Usuário')
                    ->getStateUsing(function ($record) {
                        $totalUsuario = FundoPermuta::where('usuario_id', $record->usuario_id)->sum('valor');
                        return 'R$ ' . number_format($totalUsuario, 2, ',', '.');
                    })
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('usuario_id')
                    ->label('Usuário')
                    ->relationship('usuario', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('valor_range')
                    ->form([
                        Forms\Components\TextInput::make('valor_min')
                            ->label('Valor Mínimo')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('valor_max')
                            ->label('Valor Máximo')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valor_min'],
                                fn (Builder $query, $valor): Builder => $query->where('valor', '>=', $valor),
                            )
                            ->when(
                                $data['valor_max'],
                                fn (Builder $query, $valor): Builder => $query->where('valor', '<=', $valor),
                            );
                    }),

                Tables\Filters\Filter::make('valor_alto')
                    ->label('Alto Valor (≥ R$ 10.000)')
                    ->query(fn (Builder $query): Builder => $query->where('valor', '>=', 10000)),

                Tables\Filters\Filter::make('valor_medio')
                    ->label('Médio Valor (R$ 5.000 - R$ 9.999)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('valor', [5000, 9999.99])),

                Tables\Filters\Filter::make('valor_baixo')
                    ->label('Baixo Valor (R$ 1.000 - R$ 4.999)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('valor', [1000, 4999.99])),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Criado de'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('novo_valor')
                            ->label('Novo Valor')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->step(0.01),
                    ])
                    ->action(function (FundoPermuta $record, array $data) {
                        FundoPermuta::create([
                            'valor' => $data['novo_valor'],
                            'usuario_id' => $record->usuario_id,
                        ]);

                        Notification::make()
                            ->title('Registro duplicado!')
                            ->body("Nova permuta de R$ " . number_format($data['novo_valor'], 2, ',', '.') . " criada.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('ajustar_valor')
                    ->label('Ajustar Valor')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('tipo_ajuste')
                            ->label('Tipo de Ajuste')
                            ->options([
                                'acrescentar' => 'Acrescentar',
                                'diminuir' => 'Diminuir',
                                'percentual_acrescentar' => 'Acrescentar %',
                                'percentual_diminuir' => 'Diminuir %',
                            ])
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('valor_ajuste')
                            ->label(fn ($get) => in_array($get('tipo_ajuste'), ['percentual_acrescentar', 'percentual_diminuir']) ? 'Percentual (%)' : 'Valor (R$)')
                            ->numeric()
                            ->required()
                            ->step(0.01),
                    ])
                    ->action(function (FundoPermuta $record, array $data) {
                        $valorAtual = $record->valor;
                        $valorAjuste = $data['valor_ajuste'];

                        switch ($data['tipo_ajuste']) {
                            case 'acrescentar':
                                $novoValor = $valorAtual + $valorAjuste;
                                break;
                            case 'diminuir':
                                $novoValor = max(0, $valorAtual - $valorAjuste);
                                break;
                            case 'percentual_acrescentar':
                                $novoValor = $valorAtual + ($valorAtual * $valorAjuste / 100);
                                break;
                            case 'percentual_diminuir':
                                $novoValor = max(0, $valorAtual - ($valorAtual * $valorAjuste / 100));
                                break;
                            default:
                                $novoValor = $valorAtual;
                        }

                        $record->update(['valor' => $novoValor]);

                        Notification::make()
                            ->title('Valor ajustado!')
                            ->body("Valor alterado de R$ " . number_format($valorAtual, 2, ',', '.') . " para R$ " . number_format($novoValor, 2, ',', '.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('somar_valores')
                        ->label('Somar Valores Selecionados')
                        ->icon('heroicon-o-plus')
                        ->color('info')
                        ->action(function ($records) {
                            $total = $records->sum('valor');
                            $quantidade = $records->count();

                            Notification::make()
                                ->title('Total calculado!')
                                ->body("Soma de {$quantidade} registros: R$ " . number_format($total, 2, ',', '.'))
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('ajuste_percentual')
                        ->label('Ajuste Percentual em Massa')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('tipo_ajuste')
                                ->label('Tipo de Ajuste')
                                ->options([
                                    'aumentar' => 'Aumentar %',
                                    'diminuir' => 'Diminuir %',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('percentual')
                                ->label('Percentual (%)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.1),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            $count = 0;
                            $totalAnterior = $records->sum('valor');

                            foreach ($records as $record) {
                                $valorAtual = $record->valor;

                                if ($data['tipo_ajuste'] === 'aumentar') {
                                    $novoValor = $valorAtual + ($valorAtual * $data['percentual'] / 100);
                                } else {
                                    $novoValor = max(0, $valorAtual - ($valorAtual * $data['percentual'] / 100));
                                }

                                $record->update(['valor' => $novoValor]);
                                $count++;
                            }

                            $totalPosterior = FundoPermuta::whereIn('id', $records->pluck('id'))->sum('valor');

                            Notification::make()
                                ->title("{$count} registros ajustados!")
                                ->body("Total anterior: R$ " . number_format($totalAnterior, 2, ',', '.') . " | Total posterior: R$ " . number_format($totalPosterior, 2, ',', '.'))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhum registro de permuta encontrado')
            ->emptyStateDescription('Comece registrando valores de permuta para os usuários.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundoPermutas::route('/'),
            'create' => Pages\CreateFundoPermuta::route('/create'),
            'view' => Pages\ViewFundoPermuta::route('/{record}'),
            'edit' => Pages\EditFundoPermuta::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $total = static::getModel()::sum('valor');
        return 'R$ ' . number_format($total / 1000, 0) . 'k';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $total = static::getModel()::sum('valor');
        return $total > 50000 ? 'success' : ($total > 10000 ? 'warning' : 'gray');
    }
}
