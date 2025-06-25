<?php

namespace App\Filament\Resources\ContaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CobrancasRelationManager extends RelationManager
{
    protected static string $relationship = 'cobrancas';

    protected static ?string $title = 'Cobranças';

    protected static ?string $modelLabel = 'Cobrança';

    protected static ?string $pluralModelLabel = 'Cobranças';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Cobrança')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Data de Vencimento')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pendente' => 'Pendente',
                                        'paga' => 'Paga',
                                        'vencida' => 'Vencida',
                                        'cancelada' => 'Cancelada',
                                    ])
                                    ->required()
                                    ->default('pendente')
                                    ->native(false)
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('data_pagamento')
                                    ->label('Data de Pagamento')
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->rows(3)
                            ->placeholder('Descreva o motivo da cobrança...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(2)
                            ->placeholder('Observações adicionais...')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Informações Adicionais')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('usuario_id')
                                    ->label('Usuário Responsável')
                                    ->relationship('usuario', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('sub_conta_id')
                                    ->label('Sub Conta')
                                    ->relationship('subConta', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('gerente_id')
                                    ->label('Gerente Responsável')
                                    ->relationship('gerente', 'nome')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descricao),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'paga' => 'success',
                        'vencida' => 'danger',
                        'cancelada' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pendente' => 'heroicon-o-clock',
                        'paga' => 'heroicon-o-check-circle',
                        'vencida' => 'heroicon-o-exclamation-triangle',
                        'cancelada' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->data_vencimento < now() && $record->status !== 'paga' ? 'danger' : null),

                Tables\Columns\TextColumn::make('data_pagamento')
                    ->label('Data Pagamento')
                    ->date('d/m/Y')
                    ->placeholder('Não pago')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Responsável')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('Não definido')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('subConta.nome')
                    ->label('Sub Conta')
                    ->limit(25)
                    ->placeholder('Não definido')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gerente.nome')
                    ->label('Gerente')
                    ->limit(25)
                    ->placeholder('Não definido')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dias_vencimento')
                    ->label('Dias p/ Vencimento')
                    ->getStateUsing(function ($record) {
                        if ($record->status === 'paga') {
                            return 'Pago';
                        }

                        $dias = now()->diffInDays($record->data_vencimento, false);

                        if ($dias < 0) {
                            return abs($dias) . ' dias em atraso';
                        } elseif ($dias == 0) {
                            return 'Vence hoje';
                        } else {
                            return $dias . ' dias';
                        }
                    })
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->status === 'paga' => 'success',
                        $record->data_vencimento < now() => 'danger',
                        $record->data_vencimento->diffInDays(now()) <= 3 => 'warning',
                        default => 'info'
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_vencimento', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'paga' => 'Paga',
                        'vencida' => 'Vencida',
                        'cancelada' => 'Cancelada',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder =>
                    $query->where('data_vencimento', '<', now())
                        ->where('status', '!=', 'paga')
                    ),

                Tables\Filters\Filter::make('vencendo_hoje')
                    ->label('Vencendo Hoje')
                    ->query(fn (Builder $query): Builder =>
                    $query->whereDate('data_vencimento', now())
                        ->where('status', '!=', 'paga')
                    ),

                Tables\Filters\Filter::make('proximas_vencer')
                    ->label('Próximas a Vencer (7 dias)')
                    ->query(fn (Builder $query): Builder =>
                    $query->whereBetween('data_vencimento', [now(), now()->addDays(7)])
                        ->where('status', '!=', 'paga')
                    ),

                Tables\Filters\Filter::make('periodo_vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_from')
                            ->label('Vencimento de'),
                        Forms\Components\DatePicker::make('vencimento_until')
                            ->label('Vencimento até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['vencimento_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '>=', $date),
                            )
                            ->when(
                                $data['vencimento_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Cobrança')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['conta_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('marcar_paga')
                    ->label('Marcar como Paga')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\Textarea::make('observacao_pagamento')
                            ->label('Observação do Pagamento')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'paga',
                            'data_pagamento' => $data['data_pagamento'],
                            'observacoes' => $record->observacoes . "\n\nPagamento registrado: " . ($data['observacao_pagamento'] ?? 'Sem observações'),
                        ]);

                        Notification::make()
                            ->title('Cobrança marcada como paga!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pendente', 'vencida'])),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Cobrança')
                    ->modalDescription('Tem certeza que deseja cancelar esta cobrança?')
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelada']);

                        Notification::make()
                            ->title('Cobrança cancelada!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['pendente', 'vencida'])),

                Tables\Actions\Action::make('segunda_via')
                    ->label('2ª Via')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function ($record) {
                        // Aqui você pode implementar a geração de 2ª via
                        Notification::make()
                            ->title('2ª Via gerada!')
                            ->body('Documento disponível para download ou envio.')
                            ->info()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('marcar_pagas')
                        ->label('Marcar como Pagas')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->form([
                            Forms\Components\DatePicker::make('data_pagamento')
                                ->label('Data do Pagamento')
                                ->required()
                                ->default(now())
                                ->native(false),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            $count = 0;

                            foreach ($records as $record) {
                                if (in_array($record->status, ['pendente', 'vencida'])) {
                                    $record->update([
                                        'status' => 'paga',
                                        'data_pagamento' => $data['data_pagamento'],
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} cobranças marcadas como pagas!")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('gerar_relatorio')
                        ->label('Gerar Relatório')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->action(function ($records) {
                            $total = $records->sum('valor');
                            $pagas = $records->where('status', 'paga')->count();
                            $pendentes = $records->where('status', 'pendente')->count();
                            $vencidas = $records->where('status', 'vencida')->count();

                            Notification::make()
                                ->title('Relatório de Cobranças')
                                ->body("
                                    Total: R$ " . number_format($total, 2, ',', '.') . "
                                    \nPagas: {$pagas} | Pendentes: {$pendentes} | Vencidas: {$vencidas}
                                ")
                                ->info()
                                ->persistent()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma cobrança encontrada')
            ->emptyStateDescription('Esta conta ainda não possui cobranças registradas.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
    }
}
