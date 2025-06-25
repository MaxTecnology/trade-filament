<?php

namespace App\Filament\Resources\TransacaoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VouchersRelationManager extends RelationManager
{
    protected static string $relationship = 'vouchers';

    protected static ?string $title = 'Vouchers da Transação';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Voucher')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Código do Voucher')
                            ->helperText('Código gerado automaticamente'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'Ativo' => 'Ativo',
                                'Usado' => 'Usado',
                                'Cancelado' => 'Cancelado',
                                'Expirado' => 'Expirado',
                            ])
                            ->required()
                            ->default('Ativo'),

                        Forms\Components\DateTimePicker::make('data_cancelamento')
                            ->label('Data de Cancelamento')
                            ->visible(fn ($get) => $get('status') === 'Cancelado')
                            ->helperText('Preenchido automaticamente quando cancelado'),
                    ])
                    ->columns(2),

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
                            ->label('Valor da Transação'),

                        Forms\Components\TextInput::make('transacao.nome_comprador')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => $this->ownerRecord->nome_comprador ?? '')
                            ->label('Comprador'),

                        Forms\Components\TextInput::make('transacao.nome_vendedor')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => $this->ownerRecord->nome_vendedor ?? '')
                            ->label('Vendedor'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->label('Código')
                    ->tooltip('Clique para copiar'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Usado' => 'info',
                        'Cancelado' => 'danger',
                        'Expirado' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('transacao.valor_rt')
                    ->money('BRL')
                    ->label('Valor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transacao.nome_comprador')
                    ->label('Comprador')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transacao.nome_vendedor')
                    ->label('Vendedor')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data_cancelamento')
                    ->dateTime()
                    ->label('Cancelado em')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Criado em'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Atualizado em'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Ativo' => 'Ativo',
                        'Usado' => 'Usado',
                        'Cancelado' => 'Cancelado',
                        'Expirado' => 'Expirado',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Criado de'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Criado até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('apenas_ativos')
                    ->query(fn ($query) => $query->where('status', 'Ativo'))
                    ->label('Apenas Ativos'),

                Tables\Filters\Filter::make('cancelados_hoje')
                    ->query(fn ($query) => $query->where('status', 'Cancelado')
                        ->whereDate('data_cancelamento', today()))
                    ->label('Cancelados Hoje'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['transacao_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->visible(fn () => $this->ownerRecord->emite_voucher),

                Tables\Actions\Action::make('gerar_voucher_automatico')
                    ->label('Gerar Voucher Automaticamente')
                    ->icon('heroicon-o-ticket')
                    ->color('success')
                    ->action(function () {
                        $this->ownerRecord->vouchers()->create([
                            'status' => 'Ativo',
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn () => $this->ownerRecord->emite_voucher &&
                        $this->ownerRecord->vouchers()->where('status', 'Ativo')->count() === 0),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('usar_voucher')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->action(fn ($record) => $record->update(['status' => 'Usado']))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'Ativo'),

                Tables\Actions\Action::make('cancelar_voucher')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update([
                        'status' => 'Cancelado',
                        'data_cancelamento' => now()
                    ]))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'Ativo'),

                Tables\Actions\Action::make('reativar_voucher')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(fn ($record) => $record->update([
                        'status' => 'Ativo',
                        'data_cancelamento' => null
                    ]))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['Cancelado', 'Expirado'])),

                Tables\Actions\Action::make('copiar_codigo')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function ($record) {
                        // Este seria implementado com JavaScript no frontend
                        // Por enquanto, apenas uma notificação
                        \Filament\Notifications\Notification::make()
                            ->title('Código copiado!')
                            ->body("Código: {$record->codigo}")
                            ->success()
                            ->send();
                    })
                    ->tooltip('Copiar código do voucher'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('cancelar_selecionados')
                        ->label('Cancelar Selecionados')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update([
                            'status' => 'Cancelado',
                            'data_cancelamento' => now()
                        ])))
                        ->color('danger')
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('marcar_como_usados')
                        ->label('Marcar como Usados')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'Usado'])))
                        ->color('info')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhum voucher encontrado')
            ->emptyStateDescription('Esta transação ainda não possui vouchers gerados. Se a transação permite vouchers, use o botão "Gerar Voucher Automaticamente".')
            ->emptyStateIcon('heroicon-o-ticket');
    }
}
