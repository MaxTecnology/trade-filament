<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfertaResource\Pages;
use App\Filament\Resources\OfertaResource\RelationManagers;
use App\Models\Oferta;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class OfertaResource extends Resource
{
    protected static ?string $model = Oferta::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Ofertas';

    protected static ?string $modelLabel = 'Oferta';

    protected static ?string $pluralModelLabel = 'Ofertas';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Dados da Oferta')
                    ->tabs([

                        // ===== ABA: INFORMAÇÕES BÁSICAS =====
                        Forms\Components\Tabs\Tab::make('Informações Básicas')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('titulo')
                                            ->label('Título da Oferta')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('tipo')
                                            ->label('Tipo')
                                            ->options([
                                                'produto' => 'Produto',
                                                'servico' => 'Serviço',
                                            ])
                                            ->required()
                                            ->native(false)
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('status')
                                            ->label('Ativa')
                                            ->default(true)
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('quantidade')
                                            ->label('Quantidade')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1)
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->required()
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('obs')
                                    ->label('Observações')
                                    ->rows(2)
                                    ->placeholder('Informações adicionais sobre a oferta')
                                    ->columnSpanFull(),
                            ]),

                        // ===== ABA: VALORES =====
                        Forms\Components\Tabs\Tab::make('Valores')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Configuração de Preços')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('valor')
                                                    ->label('Valor')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('limite_compra')
                                                    ->label('Limite de Compra')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->step(0.01)
                                                    ->helperText('Valor máximo que pode ser gasto nesta oferta')
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\DateTimePicker::make('vencimento')
                                            ->label('Data de Vencimento')
                                            ->required()
                                            ->native(false)
                                            ->default(now()->addDays(30)),
                                    ]),
                            ]),

                        // ===== ABA: LOCALIZAÇÃO =====
                        Forms\Components\Tabs\Tab::make('Localização')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Endereço e Entrega')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('estado')
                                                    ->label('Estado')
                                                    ->options([
                                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                                        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                                        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                                        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                                        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                                        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                                        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Select::make('retirada')
                                            ->label('Forma de Retirada')
                                            ->options([
                                                'local' => 'Retirar no Local',
                                                'entrega' => 'Entrega',
                                                'ambos' => 'Retirada ou Entrega',
                                            ])
                                            ->required()
                                            ->native(false),
                                    ]),
                            ]),

                        // ===== ABA: CATEGORIZAÇÃO =====
                        Forms\Components\Tabs\Tab::make('Categorização')
                            ->icon('heroicon-o-tag')
                            ->schema([
                                Forms\Components\Section::make('Categoria e Subcategoria')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('categoria_id')
                                                    ->label('Categoria')
                                                    ->relationship('categoria', 'nome_categoria')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('subcategoria_id')
                                                    ->label('Subcategoria')
                                                    ->options(function ($get) {
                                                        $categoriaId = $get('categoria_id');
                                                        if (!$categoriaId) {
                                                            return [];
                                                        }
                                                        return Subcategoria::where('categoria_id', $categoriaId)
                                                            ->pluck('nome_subcategoria', 'id');
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: USUÁRIO =====
                        Forms\Components\Tabs\Tab::make('Usuário')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Informações do Vendedor')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('usuario_id')
                                                    ->label('Usuário')
                                                    ->relationship('usuario', 'nome')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->live()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('nome_usuario')
                                                    ->label('Nome do Usuário')
                                                    ->maxLength(255)
                                                    ->readOnly()
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('id_franquia')
                                                    ->label('ID da Franquia')
                                                    ->numeric()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('nome_franquia')
                                                    ->label('Nome da Franquia')
                                                    ->maxLength(255)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: IMAGENS =====
                        Forms\Components\Tabs\Tab::make('Imagens')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\Section::make('Galeria de Imagens')
                                    ->description('Adicione até 10 imagens para sua oferta')
                                    ->schema([
                                        Forms\Components\FileUpload::make('imagens')
                                            ->label('Imagens da Oferta')
                                            ->image()
                                            ->multiple()
                                            ->maxFiles(10)
                                            ->maxSize(2048)
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                                            ->directory('ofertas')
                                            ->visibility('public')
                                            ->helperText('Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo: 2MB por imagem.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'produto' => 'success',
                        'servico' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('categoria.nome_categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('subcategoria.nome_subcategoria')
                    ->label('Subcategoria')
                    ->badge()
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Qtd')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->vencimento < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'produto' => 'Produto',
                        'servico' => 'Serviço',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('status')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas'),

                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome_categoria')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro', 'MG' => 'Minas Gerais',
                        'RS' => 'Rio Grande do Sul', 'PR' => 'Paraná', 'SC' => 'Santa Catarina',
                        'BA' => 'Bahia', 'GO' => 'Goiás', 'PE' => 'Pernambuco', 'CE' => 'Ceará',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => $query->where('vencimento', '<', now())),

                Tables\Filters\Filter::make('vigentes')
                    ->label('Vigentes')
                    ->query(fn (Builder $query): Builder => $query->where('vencimento', '>', now())),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Criado de'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('ativar')
                    ->label('Ativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Oferta $record) {
                        $record->update(['status' => true]);
                        Notification::make()->title('Oferta ativada!')->success()->send();
                    })
                    ->visible(fn (Oferta $record) => !$record->status),

                Tables\Actions\Action::make('desativar')
                    ->label('Desativar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Oferta $record) {
                        $record->update(['status' => false]);
                        Notification::make()->title('Oferta desativada!')->success()->send();
                    })
                    ->visible(fn (Oferta $record) => $record->status),

                Tables\Actions\Action::make('duplicar')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->action(function (Oferta $record) {
                        $novaOferta = $record->replicate();
                        $novaOferta->titulo = $record->titulo . ' (Cópia)';
                        $novaOferta->vencimento = now()->addDays(30);
                        $novaOferta->save();

                        Notification::make()
                            ->title('Oferta duplicada!')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('ativar')
                        ->label('Ativar Selecionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => true]);
                                $count++;
                            }
                            Notification::make()->title("{$count} ofertas ativadas!")->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status' => false]);
                                $count++;
                            }
                            Notification::make()->title("{$count} ofertas desativadas!")->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma oferta encontrada')
            ->emptyStateDescription('Comece criando sua primeira oferta no marketplace.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransacoesRelationManager::class,
            RelationManagers\ImagensUpRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfertas::route('/'),
            'create' => Pages\CreateOferta::route('/create'),
            'view' => Pages\ViewOferta::route('/{record}'),
            'edit' => Pages\EditOferta::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $ativas = static::getModel()::where('status', true)->count();
        return $ativas > 0 ? 'success' : 'gray';
    }
}
