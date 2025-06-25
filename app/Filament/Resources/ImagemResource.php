<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImagemResource\Pages;
use App\Filament\Resources\ImagemResource\RelationManagers;
use App\Models\Imagem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImagemResource extends Resource
{
    protected static ?string $model = Imagem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?string $modelLabel = 'Imagem';

    protected static ?string $pluralModelLabel = 'Imagens';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Imagem')
                    ->schema([
                        Forms\Components\TextInput::make('public_id')
                            ->required()
                            ->maxLength(255)
                            ->label('ID Público'),

                        Forms\Components\TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->label('URL da Imagem'),

                        Forms\Components\Select::make('oferta_id')
                            ->relationship('oferta', 'titulo')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Oferta'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Preview')
                    ->schema([
                        Forms\Components\ViewField::make('preview')
                            ->view('filament.forms.components.image-preview')
                            ->visible(fn ($get) => filled($get('url')))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Imagem')
                    ->square()
                    ->size(60),

                Tables\Columns\TextColumn::make('public_id')
                    ->searchable()
                    ->copyable()
                    ->label('ID Público'),

                Tables\Columns\TextColumn::make('oferta.titulo')
                    ->label('Oferta')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('oferta_id')
                    ->relationship('oferta', 'titulo')
                    ->label('Oferta')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->label('Ver Imagem'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImagens::route('/'),      // ✅ CORRIGIDO: ListImagens
            'create' => Pages\CreateImagem::route('/create'),
            'edit' => Pages\EditImagem::route('/{record}/edit'),
        ];
    }
}
