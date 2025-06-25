<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParcelamentoResource\Pages;
use App\Filament\Resources\ParcelamentoResource\RelationManagers;
use App\Models\Parcelamento;
use App\Models\Transacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ParcelamentoResource extends Resource
{
    protected static ?string $model = Parcelamento::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Parcelamentos';

    protected static ?string $modelLabel = 'Parcelamento';

    protected static ?string $pluralModelLabel = 'Parcelamentos';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Parcelamento')
                    ->description('Dados da parcela da transação')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('transacao_id')
                                    ->label('Transação')
                                    ->relationship('transacao', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('numero_parcela')
                                    ->label('Número da Parcela')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(1)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('valor_parcela')
                                    ->label('Valor da Parcela')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('comissao_parcela')
                                    ->label('Comissão da Parcela')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Calculadora de Parcelamento')
                    ->description('Ferramenta para calcular parcelamentos automaticamente')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('valor_total_calc')
                                    ->label('Valor Total')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->step(0.01)
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('numero_parcelas_calc')
                                    ->label('Número de Parcelas')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->live()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('percentual_comissao_calc')
                                    ->label('% Comissão')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->live()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('resultado_calc')
                            ->label('Resultado do Cálculo')
                            ->content('Configure os valores acima para ver o resultado')
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
                Tables\Columns\TextColumn::make('transacao.id')
                    ->label('Transação #')
                    ->prefix('#')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('numero_parcela')
                    ->label('Parcela')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('transacao.comprador.nome')
                    ->label('Comprador')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('transacao.vendedor.nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('transacao.oferta.titulo')
                    ->label('Oferta')
                    ->limit(30)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('valor_parcela')
                    ->label('Valor da Parcela')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('comissao_parcela')
                    ->label('Comissão')
                    ->money('BRL')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('percentual_comissao')
                    ->label('% Comissão')
                    ->getStateUsing(function ($record) {
                        if ($record->valor_parcela > 0) {
                            $percentual = ($record->comissao_parcela / $record->valor_parcela) * 100;
                            return number_format($percentual, 2) . '%';
                        }
                        return '0%';
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('valor_liquido')
                    ->label('Valor Líquido')
                    ->getStateUsing(function ($record) {
                        return $record->valor_parcela - $record->comissao_parcela;
                    })
                    ->money('BRL')
                    ->color('info'),

                Tables\Columns\TextColumn::make('transacao.status')
                    ->label('Status Transação')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'aprovada' => 'success',
                        'cancelada' => 'danger',
                        'em_andamento' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('transacao_id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('transacao_id')
                    ->label('Transação')
                    ->relationship('transacao', 'id')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('numero_parcela')
                    ->form([
                        Forms\Components\TextInput::make('parcela_min')
                            ->label('Parcela Mínima')
                            ->numeric(),
                        Forms\Components\TextInput::make('parcela_max')
                            ->label('Parcela Máxima')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['parcela_min'],
                                fn (Builder $query, $valor): Builder => $query->where('numero_parcela', '>=', $valor),
                            )
                            ->when(
                                $data['parcela_max'],
                                fn (Builder $query, $valor): Builder => $query->where('numero_parcela', '<=', $valor),
                            );
                    }),

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
                                fn (Builder $query, $valor): Builder => $query->where('valor_parcela', '>=', $valor),
                            )
                            ->when(
                                $data['valor_max'],
                                fn (Builder $query, $valor): Builder => $query->where('valor_parcela', '<=', $valor),
                            );
                    }),

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

                Tables\Actions\Action::make('calcular_percentual')
                    ->label('Recalcular %')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function (Parcelamento $record) {
                        if ($record->transacao && $record->transacao->valor_total > 0) {
                            $percentualOriginal = ($record->transacao->comissao_total / $record->transacao->valor_total) * 100;
                            $novaComissao = ($record->valor_parcela * $percentualOriginal) / 100;

                            $record->update(['comissao_parcela' => $novaComissao]);

                            Notification::make()
                                ->title('Comissão recalculada!')
                                ->body("Nova comissão: R$ " . number_format($novaComissao, 2, ',', '.'))
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('recalcular_comissoes')
                        ->label('Recalcular Comissões')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->transacao && $record->transacao->valor_total > 0) {
                                    $percentualOriginal = ($record->transacao->comissao_total / $record->transacao->valor_total) * 100;
                                    $novaComissao = ($record->valor_parcela * $percentualOriginal) / 100;
                                    $record->update(['comissao_parcela' => $novaComissao]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} comissões recalculadas!")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Selecionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            // Implementar exportação se necessário
                            Notification::make()
                                ->title('Exportação iniciada!')
                                ->body('Os dados selecionados serão processados.')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhum parcelamento encontrado')
            ->emptyStateDescription('Parcelamentos são criados automaticamente quando transações são processadas.')
            ->emptyStateIcon('heroicon-o-credit-card');
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
            'index' => Pages\ListParcelamentos::route('/'),
            'create' => Pages\CreateParcelamento::route('/create'),
            'view' => Pages\ViewParcelamento::route('/{record}'),
            'edit' => Pages\EditParcelamento::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $total = static::getModel()::count();
        return $total > 0 ? 'primary' : 'gray';
    }
}
