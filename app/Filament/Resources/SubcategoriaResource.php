<?php

// app/Filament/Resources/SubcategoriaResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcategoriaResource\Pages;
use App\Models\Subcategoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubcategoriaResource extends Resource
{
    protected static ?string $model = Subcategoria::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Configurações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('categoria_id')
                    ->relationship('categoria', 'nome_categoria')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Categoria'),
                Forms\Components\TextInput::make('nome_subcategoria')
                    ->required()
                    ->maxLength(255)
                    ->label('Nome da Subcategoria'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('categoria.nome_categoria')
                    ->searchable()
                    ->sortable()
                    ->label('Categoria'),
                Tables\Columns\TextColumn::make('nome_subcategoria')
                    ->searchable()
                    ->sortable()
                    ->label('Subcategoria'),
                Tables\Columns\TextColumn::make('usuarios_count')
                    ->counts('usuarios')
                    ->label('Usuários'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nome_categoria')
                    ->label('Categoria'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubcategorias::route('/'),
            'create' => Pages\CreateSubcategoria::route('/create'),
            'edit' => Pages\EditSubcategoria::route('/{record}/edit'),
        ];
    }
}
