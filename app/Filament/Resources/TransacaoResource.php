<?php
// app/Filament/Resources/TransacaoResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\TransacaoResource\Pages;
use App\Models\Transacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransacaoResource extends Resource
{
    protected static ?string $model = Transacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Financeiro';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Transação')
                    ->schema([
                        Forms\Components\TextInput::make('codigo')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pendente' => 'Pendente',
                                'aprovada' => 'Aprovada',
                                'cancelada' => 'Cancelada',
                                'estornada' => 'Estornada',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('data_do_estorno')
                            ->label('Data do Estorno'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Partes Envolvidas')
                    ->schema([
                        Forms\Components\Select::make('comprador_id')
                            ->relationship('comprador', 'nome')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('nome_comprador')
                            ->required(),
                        Forms\Components\Select::make('vendedor_id')
                            ->relationship('vendedor', 'nome')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('nome_vendedor')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores')
                    ->schema([
                        Forms\Components\TextInput::make('valor_rt')
                            ->numeric()
                            ->required()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('valor_adicional')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('comissao')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('comissao_parcelada')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('numero_parcelas')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Saldos')
                    ->schema([
                        Forms\Components\TextInput::make('saldo_anterior_comprador')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('saldo_apos_comprador')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('saldo_anterior_vendedor')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('saldo_apos_vendedor')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Outros')
                    ->schema([
                        Forms\Components\Select::make('oferta_id')
                            ->relationship('oferta', 'titulo')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('descricao')
                            ->required()
                            ->rows(3),
                        Forms\Components\TextInput::make('nota_atendimento')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        Forms\Components\Textarea::make('observacao_nota')
                            ->rows(2),
                        Forms\Components\Toggle::make('emite_voucher')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nome_comprador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome_vendedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valor_rt')
                    ->money('BRL')
                    ->sortable(),
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
                    }),
                Tables\Columns\IconColumn::make('emite_voucher')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'aprovada' => 'Aprovada',
                        'cancelada' => 'Cancelada',
                        'estornada' => 'Estornada',
                    ]),
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TransacaoResource\RelationManagers\ParcelamentosRelationManager::class,
            TransacaoResource\RelationManagers\VouchersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransacaos::route('/'),
            'create' => Pages\CreateTransacao::route('/create'),
            //'view' => Pages\ViewTransacao::route('/{record}'),
            'edit' => Pages\EditTransacao::route('/{record}/edit'),
        ];
    }
}
