<?php

namespace App\Filament\Resources\PlanoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContasRelationManager extends RelationManager
{
    protected static string $relationship = 'contas';

    protected static ?string $title = 'Contas Vinculadas';

    protected static ?string $modelLabel = 'Conta';

    protected static ?string $pluralModelLabel = 'Contas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero_conta')
                    ->label('Número da Conta')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('usuario_id')
                    ->label('Usuário')
                    ->relationship('usuario', 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('limite_credito')
                    ->label('Limite de Crédito')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01),

                Forms\Components\TextInput::make('saldo_permuta')
                    ->label('Saldo Permuta')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01),

                Forms\Components\TextInput::make('saldo_dinheiro')
                    ->label('Saldo Dinheiro')
                    ->numeric()
                    ->prefix('R$')
                    ->step(0.01),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_conta')
            ->columns([
                Tables\Columns\TextColumn::make('numero_conta')
                    ->label('Número da Conta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('limite_credito')
                    ->label('Limite Crédito')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_permuta')
                    ->label('Saldo Permuta')
                    ->money('BRL')
                    ->color('success'),

                Tables\Columns\TextColumn::make('saldo_dinheiro')
                    ->label('Saldo Dinheiro')
                    ->money('BRL')
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Conta'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhuma conta vinculada')
            ->emptyStateDescription('Este plano ainda não possui contas vinculadas.')
            ->emptyStateIcon('heroicon-o-credit-card');
    }
}
