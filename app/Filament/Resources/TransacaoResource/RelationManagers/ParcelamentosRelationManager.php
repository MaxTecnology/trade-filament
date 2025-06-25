<?php

namespace App\Filament\Resources\TransacaoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParcelamentosRelationManager extends RelationManager
{
    protected static string $relationship = 'parcelamentos';

    protected static ?string $title = 'Parcelamentos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Parcelamento')
                    ->schema([
                        Forms\Components\TextInput::make('numero_parcela')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Número da Parcela')
                            ->helperText('Ex: 1, 2, 3...'),

                        Forms\Components\TextInput::make('valor_parcela')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Valor da Parcela'),

                        Forms\Components\TextInput::make('comissao_parcela')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Comissão da Parcela')
                            ->helperText('Valor da comissão referente a esta parcela'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informações da Transação')
                    ->schema([
                        Forms\Components\TextInput::make('transacao.codigo')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => $this->ownerRecord->codigo ?? '')
                            ->label('Código da Transação'),

                        Forms\Components\TextInput::make('transacao.valor_rt')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => 'R$ ' . number_format($this->ownerRecord->valor_rt ?? 0, 2, ',', '.'))
                            ->label('Valor Total da Transação'),

                        Forms\Components\TextInput::make('transacao.numero_parcelas')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => $this->ownerRecord->numero_parcelas ?? '')
                            ->label('Total de Parcelas'),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_parcela')
            ->columns([
                Tables\Columns\TextColumn::make('numero_parcela')
                    ->label('Parcela')
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => "#{$state}"),

                Tables\Columns\TextColumn::make('valor_parcela')
                    ->money('BRL')
                    ->label('Valor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('comissao_parcela')
                    ->money('BRL')
                    ->label('Comissão')
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentual_valor')
                    ->label('% do Total')
                    ->getStateUsing(function ($record) {
                        $valorTotal = $record->transacao->valor_rt ?? 0;
                        if ($valorTotal > 0) {
                            $percentual = ($record->valor_parcela / $valorTotal) * 100;
                            return number_format($percentual, 1) . '%';
                        }
                        return '0%';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('percentual_comissao')
                    ->label('% Comissão')
                    ->getStateUsing(function ($record) {
                        if ($record->valor_parcela > 0) {
                            $percentual = ($record->comissao_parcela / $record->valor_parcela) * 100;
                            return number_format($percentual, 1) . '%';
                        }
                        return '0%';
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Atualizada em'),
            ])
            ->filters([
                Tables\Filters\Filter::make('valor')
                    ->form([
                        Forms\Components\TextInput::make('valor_min')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('valor_max')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['valor_min'], fn ($query, $value) => $query->where('valor_parcela', '>=', $value))
                            ->when($data['valor_max'], fn ($query, $value) => $query->where('valor_parcela', '<=', $value));
                    }),

                Tables\Filters\Filter::make('comissao')
                    ->form([
                        Forms\Components\TextInput::make('comissao_min')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('comissao_max')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['comissao_min'], fn ($query, $value) => $query->where('comissao_parcela', '>=', $value))
                            ->when($data['comissao_max'], fn ($query, $value) => $query->where('comissao_parcela', '<=', $value));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['transacao_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->before(function (array $data) {
                        // Validar se o número da parcela não existe
                        $exists = $this->ownerRecord->parcelamentos()
                            ->where('numero_parcela', $data['numero_parcela'])
                            ->exists();

                        if ($exists) {
                            throw new \Exception("Já existe uma parcela com o número {$data['numero_parcela']}");
                        }
                    }),

                Tables\Actions\Action::make('gerar_parcelas_automaticas')
                    ->label('Gerar Parcelas Automaticamente')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('numero_parcelas')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(12)
                            ->default(fn () => $this->ownerRecord->numero_parcelas)
                            ->label('Número de Parcelas'),
                        Forms\Components\Toggle::make('substituir_existentes')
                            ->label('Substituir parcelas existentes')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $transacao = $this->ownerRecord;
                        $numeroParcelas = $data['numero_parcelas'];
                        $valorPorParcela = $transacao->valor_rt / $numeroParcelas;
                        $comissaoPorParcela = $transacao->comissao / $numeroParcelas;

                        if ($data['substituir_existentes']) {
                            $transacao->parcelamentos()->delete();
                        }

                        for ($i = 1; $i <= $numeroParcelas; $i++) {
                            $transacao->parcelamentos()->updateOrCreate(
                                ['numero_parcela' => $i],
                                [
                                    'valor_parcela' => $valorPorParcela,
                                    'comissao_parcela' => $comissaoPorParcela,
                                ]
                            );
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn () => $this->ownerRecord->valor_rt > 0),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('recalcular')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function ($record) {
                        $transacao = $record->transacao;
                        $totalParcelas = $transacao->numero_parcelas;

                        if ($totalParcelas > 0) {
                            $valorPorParcela = $transacao->valor_rt / $totalParcelas;
                            $comissaoPorParcela = $transacao->comissao / $totalParcelas;

                            $record->update([
                                'valor_parcela' => $valorPorParcela,
                                'comissao_parcela' => $comissaoPorParcela,
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->tooltip('Recalcular valores baseado no total da transação'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('recalcular_todas')
                        ->label('Recalcular Selecionadas')
                        ->icon('heroicon-o-calculator')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $transacao = $record->transacao;
                                $totalParcelas = $transacao->numero_parcelas;

                                if ($totalParcelas > 0) {
                                    $valorPorParcela = $transacao->valor_rt / $totalParcelas;
                                    $comissaoPorParcela = $transacao->comissao / $totalParcelas;

                                    $record->update([
                                        'valor_parcela' => $valorPorParcela,
                                        'comissao_parcela' => $comissaoPorParcela,
                                    ]);
                                }
                            }
                        })
                        ->color('warning')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('numero_parcela', 'asc')
            ->emptyStateHeading('Nenhuma parcela encontrada')
            ->emptyStateDescription('Esta transação ainda não possui parcelas configuradas. Use o botão "Gerar Parcelas Automaticamente" para criar rapidamente.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
