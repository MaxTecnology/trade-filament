<?php
// app/Filament/Resources/CategoriaResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaResource\Pages;
use App\Models\Categoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Configurações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome_categoria')
                    ->required()
                    ->maxLength(255)
                    ->label('Nome da Categoria'),
                Forms\Components\Select::make('tipo_categoria')
                    ->options([
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                        'ambos' => 'Ambos',
                    ])
                    ->label('Tipo da Categoria'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome_categoria')
                    ->searchable()
                    ->sortable()
                    ->label('Nome da Categoria'),
                Tables\Columns\TextColumn::make('tipo_categoria')
                    ->searchable()
                    ->sortable()
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('subcategorias_count')
                    ->counts('subcategorias')
                    ->label('Subcategorias'),
                Tables\Columns\TextColumn::make('usuarios_count')
                    ->counts('usuarios')
                    ->label('Usuários'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_categoria')
                    ->options([
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                        'ambos' => 'Ambos',
                    ]),
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

    public static function getRelations(): array
    {
        return [
            CategoriaResource\RelationManagers\SubcategoriasRelationManager::class,
            CategoriaResource\RelationManagers\OfertasRelationManager::class,
            CategoriaResource\RelationManagers\UsuariosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit' => Pages\EditCategoria::route('/{record}/edit'),
        ];
    }
}

