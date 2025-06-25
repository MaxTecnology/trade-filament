<?php

namespace App\Filament\Resources\UsuarioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransacoesCompradorRelationManager extends RelationManager
{
    protected static string $relationship = 'transacoesComprador';

    protected static ?string $title = 'Transações como Comprador';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Transação')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Código da Transação'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pendente' => 'Pendente',
                                'aprovada' => 'Aprovada',
                                'cancelada' => 'Cancelada',
                                'estornada' => 'Estornada',
                            ])
                            ->required()
                            ->default('pendente'),

                        Forms\Components\DateTimePicker::make('data_do_estorno')
                            ->label('Data do Estorno')
                            ->visible(fn ($get) => $get('status') === 'estornada'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('valor_rt')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Valor da Transação'),

                        Forms\Components\TextInput::make('valor_adicional')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->default(0)
                            ->label('Valor Adicional'),

                        Forms\Components\TextInput::make('comissao')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Comissão'),

                        Forms\Components\TextInput::make('numero_parcelas')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->label('Número de Parcelas'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Vendedor')
                    ->schema([
                        Forms\Components\Select::make('vendedor_id')
                            ->relationship('vendedor', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Vendedor')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $vendedor = \App\Models\Usuario::find($state);
                                    $set('nome_vendedor', $vendedor?->nome ?? '');
                                }
                            }),

                        Forms\Components\TextInput::make('nome_vendedor')
                            ->required()
                            ->label('Nome do Vendedor'),

                        Forms\Components\Select::make('oferta_id')
                            ->relationship('oferta', 'titulo')
                            ->searchable()
                            ->preload()
                            ->label('Oferta Relacionada'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Descrição e Avaliação')
                    ->schema([
                        Forms\Components\Textarea::make('descricao')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('nota_atendimento')
                            ->options([
                                1 => '⭐ (1) - Muito Ruim',
                                2 => '⭐⭐ (2) - Ruim',
                                3 => '⭐⭐⭐ (3) - Regular',
                                4 => '⭐⭐⭐⭐ (4) - Bom',
                                5 => '⭐⭐⭐⭐⭐ (5) - Excelente',
                            ])
                            ->required()
                            ->label('Avaliação do Atendimento'),

                        Forms\Components\Textarea::make('observacao_nota')
                            ->rows(2)
                            ->label('Observação da Avaliação')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('emite_voucher')
                            ->label('Emite Voucher')
                            ->default(false),
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
                    ->copyable()
                    ->searchable()
                    ->weight('bold')
                    ->label('Código'),

                Tables\Columns\TextColumn::make('nome_vendedor')
                    ->label('Vendedor')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('oferta.titulo')
                    ->label('Oferta')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valor_rt')
                    ->money('BRL')
                    ->sortable()
                    ->label('Valor'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aprovada' => 'success',
                        'pendente' => 'warning',
                        'cancelada' => 'danger',
                        'estornada' => 'gray',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('nota_atendimento')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        $state >= 2 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state ?? 0))
                    ->label('Avaliação'),

                Tables\Columns\TextColumn::make('numero_parcelas')
                    ->badge()
                    ->color('info')
                    ->label('Parcelas'),

                Tables\Columns\IconColumn::make('emite_voucher')
                    ->boolean()
                    ->label('Voucher'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Data'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovada' => 'Aprovada',
                        'cancelada' => 'Cancelada',
                        'estornada' => 'Estornada',
                    ]),

                Tables\Filters\SelectFilter::make('nota_atendimento')
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ (5)',
                        4 => '⭐⭐⭐⭐ (4)',
                        3 => '⭐⭐⭐ (3)',
                        2 => '⭐⭐ (2)',
                        1 => '⭐ (1)',
                    ])
                    ->label('Avaliação'),

                Tables\Filters\TernaryFilter::make('emite_voucher')
                    ->label('Emite Voucher'),

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
                            ->when($data['valor_min'], fn ($query, $value) => $query->where('valor_rt', '>=', $value))
                            ->when($data['valor_max'], fn ($query, $value) => $query->where('valor_rt', '<=', $value));
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['comprador_id'] = $this->ownerRecord->id;
                        $data['nome_comprador'] = $this->ownerRecord->nome;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'aprovada']))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pendente'),
                Tables\Actions\Action::make('cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['status' => 'cancelada']))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pendente', 'aprovada'])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('aprovar')
                        ->label('Aprovar Selecionadas')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'aprovada'])))
                        ->color('success')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma transação encontrada')
            ->emptyStateDescription('Este usuário ainda não realizou compras.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
