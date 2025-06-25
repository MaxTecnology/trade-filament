<?php

namespace App\Filament\Resources\UsuarioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CobrancasRelationManager extends RelationManager
{
    protected static string $relationship = 'cobrancas';

    protected static ?string $title = 'Cobranças do Usuário';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Cobrança')
                    ->schema([
                        Forms\Components\TextInput::make('valor_fatura')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Valor da Fatura'),

                        Forms\Components\TextInput::make('referencia')
                            ->required()
                            ->maxLength(255)
                            ->label('Referência')
                            ->helperText('Ex: Janeiro/2024, Taxa de Manutenção, etc.'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pendente' => 'Pendente',
                                'paga' => 'Paga',
                                'vencida' => 'Vencida',
                                'cancelada' => 'Cancelada',
                                'em_analise' => 'Em Análise',
                                'parcial' => 'Paga Parcialmente',
                            ])
                            ->required()
                            ->default('pendente'),

                        Forms\Components\DateTimePicker::make('vencimento_fatura')
                            ->label('Data de Vencimento')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Relacionamentos')
                    ->schema([
                        Forms\Components\Select::make('conta_id')
                            ->relationship('conta', 'numero_conta')
                            ->searchable()
                            ->preload()
                            ->label('Conta Relacionada'),

                        Forms\Components\Select::make('transacao_id')
                            ->relationship('transacao', 'codigo')
                            ->searchable()
                            ->preload()
                            ->label('Transação Relacionada'),

                        Forms\Components\Select::make('gerente_conta_id')
                            ->relationship('gerente', 'nome')
                            ->searchable()
                            ->preload()
                            ->label('Gerente Responsável'),

                        Forms\Components\Select::make('sub_conta_id')
                            ->relationship('subConta', 'nome')
                            ->searchable()
                            ->preload()
                            ->label('Sub Conta'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('referencia')
            ->columns([
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Referência'),

                Tables\Columns\TextColumn::make('valor_fatura')
                    ->money('BRL')
                    ->sortable()
                    ->label('Valor'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paga' => 'success',
                        'pendente' => 'warning',
                        'vencida' => 'danger',
                        'cancelada' => 'gray',
                        'em_analise' => 'info',
                        'parcial' => 'primary',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('vencimento_fatura')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->vencida ? 'danger' : 'success')
                    ->label('Vencimento'),

                Tables\Columns\TextColumn::make('conta.numero_conta')
                    ->label('Conta')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('transacao.codigo')
                    ->label('Transação')
                    ->copyable()
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('gerente.nome')
                    ->label('Gerente')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'paga' => 'Paga',
                        'vencida' => 'Vencida',
                        'cancelada' => 'Cancelada',
                        'em_analise' => 'Em Análise',
                        'parcial' => 'Paga Parcialmente',
                    ]),

                Tables\Filters\Filter::make('vencidas')
                    ->query(fn ($query) => $query->where('vencimento_fatura', '<', now()))
                    ->label('Vencidas'),

                Tables\Filters\Filter::make('a_vencer')
                    ->query(fn ($query) => $query->whereBetween('vencimento_fatura', [now(), now()->addDays(7)]))
                    ->label('Vencem em 7 dias'),

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
                            ->when($data['valor_min'], fn ($query, $value) => $query->where('valor_fatura', '>=', $value))
                            ->when($data['valor_max'], fn ($query, $value) => $query->where('valor_fatura', '<=', $value));
                    }),

                Tables\Filters\Filter::make('vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_de')
                            ->label('Vencimento de'),
                        Forms\Components\DatePicker::make('vencimento_ate')
                            ->label('Vencimento até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['vencimento_de'], fn ($query, $date) => $query->whereDate('vencimento_fatura', '>=', $date))
                            ->when($data['vencimento_ate'], fn ($query, $date) => $query->whereDate('vencimento_fatura', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('marcar_paga')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'paga']))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'paga'),
                Tables\Actions\Action::make('marcar_vencida')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['status' => 'vencida']))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pendente'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('marcar_pagas')
                        ->label('Marcar como Pagas')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'paga'])))
                        ->color('success')
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('marcar_vencidas')
                        ->label('Marcar como Vencidas')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'vencida'])))
                        ->color('danger')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('vencimento_fatura', 'asc')
            ->emptyStateHeading('Nenhuma cobrança encontrada')
            ->emptyStateDescription('Este usuário ainda não possui cobranças registradas.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
