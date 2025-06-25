<?php

// app/Filament/Resources/TipoContaResource.php (atualizado para incluir RelationManagers)

namespace App\Filament\Resources;

use App\Filament\Resources\TipoContaResource\Pages;
use App\Filament\Resources\TipoContaResource\RelationManagers;
use App\Models\TipoConta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TipoContaResource extends Resource
{
    protected static ?string $model = TipoConta::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Tipo de Conta';

    protected static ?string $pluralModelLabel = 'Tipos de Conta';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Tipo de Conta')
                    ->schema([
                        Forms\Components\TextInput::make('tipo_da_conta')
                            ->required()
                            ->maxLength(255)
                            ->label('Tipo da Conta'),

                        Forms\Components\TextInput::make('prefixo_conta')
                            ->required()
                            ->maxLength(10)
                            ->label('Prefixo da Conta')
                            ->helperText('Prefixo usado na numeração das contas (ex: PF, PJ, FR)'),

                        Forms\Components\Textarea::make('descricao')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permissões')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissoes')
                            ->options([
                                'comprar' => 'Comprar',
                                'vender' => 'Vender',
                                'transferir' => 'Transferir',
                                'emitir_nota_fiscal' => 'Emitir Nota Fiscal',
                                'gerenciar_funcionarios' => 'Gerenciar Funcionários',
                                'gerenciar_matriz' => 'Gerenciar Matriz',
                                'administrar_sistema' => 'Administrar Sistema',
                                'aprovar_credito' => 'Aprovar Crédito',
                                'visualizar_relatorios' => 'Visualizar Relatórios',
                                'gerenciar_cobrancas' => 'Gerenciar Cobranças',
                            ])
                            ->columns(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo_da_conta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('prefixo_conta')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('contas_count')
                    ->counts('contas')
                    ->label('Contas')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('permissoes')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (TipoConta $record) {
                        if ($record->contas()->count() > 0) {
                            throw new \Exception('Não é possível excluir um tipo de conta que possui contas associadas.');
                        }
                    }),
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
            RelationManagers\ContasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoContas::route('/'),
            'create' => Pages\CreateTipoConta::route('/create'),
            //'view' => Pages\ViewTipoConta::route('/{record}'),
            'edit' => Pages\EditTipoConta::route('/{record}/edit'),
        ];
    }
}
