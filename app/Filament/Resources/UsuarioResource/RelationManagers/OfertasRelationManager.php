<?php

namespace App\Filament\Resources\UsuarioResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OfertasRelationManager extends RelationManager
{
    protected static string $relationship = 'ofertas';

    protected static ?string $title = 'Ofertas do Usuário';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tipo')
                            ->options([
                                'produto' => 'Produto',
                                'servico' => 'Serviço',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('status')
                            ->default(true)
                            ->label('Ativa'),

                        Forms\Components\Textarea::make('descricao')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Valores e Quantidade')
                    ->schema([
                        Forms\Components\TextInput::make('valor')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01),

                        Forms\Components\TextInput::make('limite_compra')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->helperText('Valor máximo por compra'),

                        Forms\Components\TextInput::make('quantidade')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\DateTimePicker::make('vencimento')
                            ->required()
                            ->minDate(now())
                            ->helperText('Data limite para a oferta'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Localização')
                    ->schema([
                        Forms\Components\TextInput::make('cidade')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('estado')
                            ->options([
                                'AC' => 'Acre',
                                'AL' => 'Alagoas',
                                'AP' => 'Amapá',
                                'AM' => 'Amazonas',
                                'BA' => 'Bahia',
                                'CE' => 'Ceará',
                                'DF' => 'Distrito Federal',
                                'ES' => 'Espírito Santo',
                                'GO' => 'Goiás',
                                'MA' => 'Maranhão',
                                'MT' => 'Mato Grosso',
                                'MS' => 'Mato Grosso do Sul',
                                'MG' => 'Minas Gerais',
                                'PA' => 'Pará',
                                'PB' => 'Paraíba',
                                'PR' => 'Paraná',
                                'PE' => 'Pernambuco',
                                'PI' => 'Piauí',
                                'RJ' => 'Rio de Janeiro',
                                'RN' => 'Rio Grande do Norte',
                                'RS' => 'Rio Grande do Sul',
                                'RO' => 'Rondônia',
                                'RR' => 'Roraima',
                                'SC' => 'Santa Catarina',
                                'SP' => 'São Paulo',
                                'SE' => 'Sergipe',
                                'TO' => 'Tocantins',
                            ])
                            ->required(),

                        Forms\Components\Select::make('retirada')
                            ->options([
                                'local' => 'Retirada no Local',
                                'entrega' => 'Entrega',
                                'ambos' => 'Ambos',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('obs')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Categorização')
                    ->schema([
                        Forms\Components\Select::make('categoria_id')
                            ->relationship('categoria', 'nome_categoria')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('subcategoria_id', null)),

                        Forms\Components\Select::make('subcategoria_id')
                            ->relationship('subcategoria', 'nome_subcategoria',
                                fn ($query, $get) => $query->where('categoria_id', $get('categoria_id')))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Outros')
                    ->schema([
                        Forms\Components\TextInput::make('nome_usuario')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn () => $this->ownerRecord->nome)
                            ->label('Nome do Usuário'),

                        Forms\Components\FileUpload::make('imagens')
                            ->image()
                            ->multiple()
                            ->directory('ofertas')
                            ->maxFiles(10)
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->columns([
                Tables\Columns\ImageColumn::make('imagens')
                    ->limit(1)
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('titulo')
                    ->limit(30)
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'produto' => 'success',
                        'servico' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantidade')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label('Ativa'),

                Tables\Columns\TextColumn::make('categoria.nome_categoria')
                    ->label('Categoria')
                    ->limit(20),

                Tables\Columns\TextColumn::make('cidade')
                    ->searchable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('estado')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('vencimento')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->vencimento < now() ? 'danger' : 'success')
                    ->label('Vencimento'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status Ativo'),

                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                    ]),

                Tables\Filters\SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nome_categoria')
                    ->label('Categoria'),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'SP' => 'São Paulo',
                        'RJ' => 'Rio de Janeiro',
                        'MG' => 'Minas Gerais',
                        'RS' => 'Rio Grande do Sul',
                        'PR' => 'Paraná',
                        'SC' => 'Santa Catarina',
                        'BA' => 'Bahia',
                        'GO' => 'Goiás',
                        'PE' => 'Pernambuco',
                        'CE' => 'Ceará',
                    ]),

                Tables\Filters\Filter::make('vencimento_futuro')
                    ->query(fn ($query) => $query->where('vencimento', '>', now()))
                    ->label('Não Vencidas'),

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
                            ->when($data['valor_min'], fn ($query, $value) => $query->where('valor', '>=', $value))
                            ->when($data['valor_max'], fn ($query, $value) => $query->where('valor', '<=', $value));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->ownerRecord->id;
                        $data['nome_usuario'] = $this->ownerRecord->nome;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function ($record) {
                        $newRecord = $record->replicate();
                        $newRecord->titulo = $record->titulo . ' (Cópia)';
                        $newRecord->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('ativar')
                        ->label('Ativar Selecionadas')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => true])))
                        ->color('success'),
                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionadas')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => false])))
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma oferta encontrada')
            ->emptyStateDescription('Este usuário ainda não possui ofertas cadastradas.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
