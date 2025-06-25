<?php

// app/Filament/Resources/TipoContaResource/RelationManagers/ContasRelationManager.php

namespace App\Filament\Resources\TipoContaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ContasRelationManager extends RelationManager
{
    protected static string $relationship = 'contas';

    protected static ?string $title = 'Contas Associadas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero_conta')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Select::make('usuario_id')
                    ->relationship('usuario', 'nome')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('plano_id')
                    ->relationship('plano', 'nome_plano')
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('limite_credito')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01)
                    ->default(0.00),

                Forms\Components\TextInput::make('saldo_permuta')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01)
                    ->default(0.00),

                Forms\Components\TextInput::make('saldo_dinheiro')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01)
                    ->default(0.00),

                Forms\Components\TextInput::make('limite_venda_mensal')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01)
                    ->required(),

                Forms\Components\TextInput::make('limite_venda_total')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01)
                    ->required(),

                Forms\Components\TextInput::make('dia_fechamento_fatura')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(31)
                    ->required(),

                Forms\Components\TextInput::make('data_vencimento_fatura')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(31)
                    ->required(),

                Forms\Components\DatePicker::make('data_de_afiliacao')
                    ->default(now()),

                Forms\Components\TextInput::make('nome_franquia')
                    ->maxLength(255),

                Forms\Components\Select::make('gerente_conta_id')
                    ->relationship('gerenteConta', 'nome')
                    ->searchable()
                    ->preload()
                    ->label('Gerente da Conta'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_conta')
            ->columns([
                Tables\Columns\TextColumn::make('numero_conta')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Titular')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('limite_credito')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_permuta')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_dinheiro')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plano.nome_plano')
                    ->label('Plano')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('gerenteConta.nome')
                    ->label('Gerente')
                    ->limit(20),

                Tables\Columns\TextColumn::make('data_de_afiliacao')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plano_id')
                    ->relationship('plano', 'nome_plano')
                    ->label('Plano'),

                Tables\Filters\SelectFilter::make('gerente_conta_id')
                    ->relationship('gerenteConta', 'nome')
                    ->label('Gerente'),

                Tables\Filters\Filter::make('data_afiliacao')
                    ->form([
                        Forms\Components\DatePicker::make('afiliacao_de')
                            ->label('Afiliação de'),
                        Forms\Components\DatePicker::make('afiliacao_ate')
                            ->label('Afiliação até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['afiliacao_de'], fn ($query, $date) => $query->whereDate('data_de_afiliacao', '>=', $date))
                            ->when($data['afiliacao_ate'], fn ($query, $date) => $query->whereDate('data_de_afiliacao', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
