<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContaResource\Pages;
use App\Filament\Resources\ContaResource\RelationManagers;
use App\Models\Conta;
use App\Models\Usuario;
use App\Models\TipoConta;
use App\Models\Plano;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class ContaResource extends Resource
{
    protected static ?string $model = Conta::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Contas';

    protected static ?string $modelLabel = 'Conta';

    protected static ?string $pluralModelLabel = 'Contas';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Dados da Conta')
                    ->tabs([

                        // ===== ABA: INFORMAÇÕES BÁSICAS =====
                        Forms\Components\Tabs\Tab::make('Informações Básicas')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('usuario_id')
                                            ->label('Usuário')
                                            ->relationship('usuario', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('numero_conta')
                                            ->label('Número da Conta')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50)
                                            ->default(fn() => 'PJ' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT))
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('tipo_conta_id')
                                            ->label('Tipo de Conta')
                                            ->relationship('tipoConta', 'tipo_da_conta')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('plano_id')
                                            ->label('Plano')
                                            ->relationship('plano', 'nome_plano')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('gerente_conta_id')
                                            ->label('Gerente da Conta')
                                            ->relationship('gerenteConta', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->columnSpan(1),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('data_de_afiliacao')
                                            ->label('Data de Afiliação')
                                            ->default(now())
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('nome_franquia')
                                            ->label('Nome da Franquia')
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                    ]),
                            ]),

                        // ===== ABA: LIMITES E SALDOS =====
                        Forms\Components\Tabs\Tab::make('Limites e Saldos')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Limites de Crédito')
                                    ->description('Configure os limites de crédito e uso')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('limite_credito')
                                                    ->label('Limite de Crédito')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('limite_utilizado')
                                                    ->label('Limite Utilizado')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->readOnly()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('limite_disponivel')
                                                    ->label('Limite Disponível')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->readOnly()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Saldos')
                                    ->description('Saldos atuais da conta')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('saldo_permuta')
                                                    ->label('Saldo Permuta')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('saldo_dinheiro')
                                                    ->label('Saldo Dinheiro')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: LIMITES DE VENDA =====
                        Forms\Components\Tabs\Tab::make('Limites de Venda')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Forms\Components\Section::make('Limites de Venda')
                                    ->description('Configure os limites de vendas')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('limite_venda_mensal')
                                                    ->label('Limite Venda Mensal')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('limite_venda_total')
                                                    ->label('Limite Venda Total')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('limite_venda_empresa')
                                                    ->label('Limite Venda Empresa')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Vendas Atuais')
                                    ->description('Valores de vendas realizadas')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('valor_venda_mensal_atual')
                                                    ->label('Vendas Mensal Atual')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->readOnly()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('valor_venda_total_atual')
                                                    ->label('Vendas Total Atual')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->readOnly()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: CONFIGURAÇÕES =====
                        Forms\Components\Tabs\Tab::make('Configurações')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Configurações de Fatura')
                                    ->description('Configure datas e taxas')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('taxa_repasse_matriz')
                                                    ->label('Taxa Repasse Matriz (%)')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('dia_fechamento_fatura')
                                                    ->label('Dia Fechamento Fatura')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->maxValue(31)
                                                    ->default(5)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('data_vencimento_fatura')
                                                    ->label('Dia Vencimento Fatura')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->maxValue(31)
                                                    ->default(10)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Permissões Específicas')
                                    ->description('Configure permissões especiais para esta conta')
                                    ->schema([
                                        Forms\Components\KeyValue::make('permissoes_especificas')
                                            ->label('Permissões Personalizadas')
                                            ->keyLabel('Permissão')
                                            ->valueLabel('Valor')
                                            ->addActionLabel('Adicionar Permissão')
                                            ->nullable(),
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
                Tables\Columns\TextColumn::make('numero_conta')
                    ->label('Número')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipoConta.tipo_da_conta')
                    ->label('Tipo de Conta')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pessoa Física' => 'info',
                        'Pessoa Jurídica' => 'success',
                        'Franquia' => 'warning',
                        'Matriz' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('plano.nome_plano')
                    ->label('Plano')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('limite_credito')
                    ->label('Limite Crédito')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('saldo_permuta')
                    ->label('Saldo Permuta')
                    ->money('BRL')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('saldo_dinheiro')
                    ->label('Saldo Dinheiro')
                    ->money('BRL')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('gerenteConta.nome')
                    ->label('Gerente')
                    ->limit(20)
                    ->placeholder('Não definido'),

                Tables\Columns\TextColumn::make('data_de_afiliacao')
                    ->label('Afiliação')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_conta_id')
                    ->label('Tipo de Conta')
                    ->relationship('tipoConta', 'tipo_da_conta')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('plano_id')
                    ->label('Plano')
                    ->relationship('plano', 'nome_plano')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('gerente_conta_id')
                    ->label('Gerente')
                    ->relationship('gerenteConta', 'nome')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('com_saldo_positivo')
                    ->label('Com Saldo Positivo')
                    ->query(fn (Builder $query): Builder => $query->where('saldo_permuta', '>', 0)),

                Tables\Filters\Filter::make('limite_disponivel')
                    ->label('Com Limite Disponível')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('limite_credito > limite_utilizado')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('resetar_senha')
                    ->label('Resetar Limites')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Conta $record) {
                        $record->update([
                            'limite_utilizado' => 0,
                            'valor_venda_mensal_atual' => 0,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Selecionadas')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            // Implementar exportação
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma conta encontrada')
            ->emptyStateDescription('Comece criando sua primeira conta no sistema.')
            ->emptyStateIcon('heroicon-o-credit-card');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubContasRelationManager::class,
            RelationManagers\CobrancasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContas::route('/'),
            'create' => Pages\CreateConta::route('/create'),
            'view' => Pages\ViewConta::route('/{record}'),
            'edit' => Pages\EditConta::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'success' : 'primary';
    }
}
