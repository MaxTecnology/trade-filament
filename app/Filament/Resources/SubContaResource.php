<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubContaResource\Pages;
use App\Filament\Resources\SubContaResource\RelationManagers;
use App\Models\SubConta;
use App\Models\Conta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class SubContaResource extends Resource
{
    protected static ?string $model = SubConta::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Sub Contas';

    protected static ?string $modelLabel = 'Sub Conta';

    protected static ?string $pluralModelLabel = 'Sub Contas';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Dados da Sub Conta')
                    ->tabs([

                        // ===== ABA: INFORMAÇÕES PESSOAIS =====
                        Forms\Components\Tabs\Tab::make('Informações Pessoais')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Dados Básicos')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('nome')
                                                    ->label('Nome Completo')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('email')
                                                    ->label('Email')
                                                    ->email()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('cpf')
                                                    ->label('CPF')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->mask('999.999.999-99')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('numero_sub_conta')
                                                    ->label('Número da Sub Conta')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->default(fn() => 'SUB' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT))
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('reputacao')
                                                    ->label('Reputação')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(5)
                                                    ->step(0.1)
                                                    ->default(0)
                                                    ->suffix('/ 5')
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('senha')
                                                    ->label('Senha')
                                                    ->password()
                                                    ->required(fn ($context) => $context === 'create')
                                                    ->minLength(6)
                                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                                    ->dehydrated(fn ($state) => filled($state))
                                                    ->columnSpan(1),

                                                Forms\Components\Toggle::make('status_conta')
                                                    ->label('Conta Ativa')
                                                    ->default(true)
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\FileUpload::make('imagem')
                                            ->label('Foto de Perfil')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios(['1:1'])
                                            ->directory('sub-contas')
                                            ->visibility('public')
                                            ->maxSize(2048),
                                    ]),
                            ]),

                        // ===== ABA: CONTATO =====
                        Forms\Components\Tabs\Tab::make('Contato')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\Section::make('Informações de Contato')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('telefone')
                                                    ->label('Telefone')
                                                    ->tel()
                                                    ->mask('(99) 9999-9999')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('celular')
                                                    ->label('Celular')
                                                    ->tel()
                                                    ->mask('(99) 99999-9999')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('email_contato')
                                                    ->label('Email Alternativo')
                                                    ->email()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: ENDEREÇO =====
                        Forms\Components\Tabs\Tab::make('Endereço')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Section::make('Endereço Completo')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('cep')
                                                    ->label('CEP')
                                                    ->mask('99999-999')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->columnSpan(2),
                                            ]),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('numero')
                                                    ->label('Número')
                                                    ->numeric()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('complemento')
                                                    ->label('Complemento')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('bairro')
                                                    ->label('Bairro')
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
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
                                                    ->searchable()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ===== ABA: CONTA PAI =====
                        Forms\Components\Tabs\Tab::make('Conta Pai')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make('Vinculação com Conta Principal')
                                    ->schema([
                                        Forms\Components\Select::make('conta_pai_id')
                                            ->label('Conta Pai')
                                            ->relationship('contaPai', 'numero_conta')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->helperText('Selecione a conta principal à qual esta sub conta será vinculada'),
                                    ]),
                            ]),

                        // ===== ABA: PERMISSÕES =====
                        Forms\Components\Tabs\Tab::make('Permissões')
                            ->icon('heroicon-o-key')
                            ->schema([
                                Forms\Components\Section::make('Configuração de Permissões')
                                    ->description('Defina quais ações esta sub conta pode realizar')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('permissoes')
                                            ->label('Permissões')
                                            ->options([
                                                'criar_ofertas' => 'Criar Ofertas',
                                                'editar_ofertas' => 'Editar Ofertas',
                                                'excluir_ofertas' => 'Excluir Ofertas',
                                                'visualizar_ofertas' => 'Visualizar Ofertas',
                                                'criar_transacoes' => 'Criar Transações',
                                                'aprovar_transacoes' => 'Aprovar Transações',
                                                'cancelar_transacoes' => 'Cancelar Transações',
                                                'visualizar_relatorios' => 'Visualizar Relatórios',
                                                'gerenciar_usuarios' => 'Gerenciar Usuários',
                                                'configurar_sistema' => 'Configurar Sistema',
                                                'acessar_financeiro' => 'Acessar Módulo Financeiro',
                                                'emitir_vouchers' => 'Emitir Vouchers',
                                                'cancelar_vouchers' => 'Cancelar Vouchers',
                                            ])
                                            ->columns(2)
                                            ->default([
                                                'criar_ofertas',
                                                'editar_ofertas',
                                                'visualizar_ofertas',
                                                'criar_transacoes',
                                            ]),
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
                Tables\Columns\ImageColumn::make('imagem')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=' . urlencode('User') . '&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_sub_conta')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Número copiado!'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->limit(30)
                    ->copyable(),

                Tables\Columns\TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string =>
                        substr($state, 0, 3) . '.***.**' . substr($state, -2)
                    ),

                Tables\Columns\TextColumn::make('contaPai.numero_conta')
                    ->label('Conta Pai')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\IconColumn::make('status_conta')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('reputacao')
                    ->label('Reputação')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state >= 2.0 => 'info',
                        default => 'danger'
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '/5'),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('status_conta')
                    ->label('Status da Conta')
                    ->placeholder('Todos')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas'),

                Tables\Filters\SelectFilter::make('conta_pai_id')
                    ->label('Conta Pai')
                    ->relationship('contaPai', 'numero_conta')
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

                Tables\Filters\Filter::make('reputacao')
                    ->form([
                        Forms\Components\Select::make('reputacao_min')
                            ->label('Reputação Mínima')
                            ->options([
                                '0' => '0 estrelas',
                                '1' => '1 estrela',
                                '2' => '2 estrelas',
                                '3' => '3 estrelas',
                                '4' => '4 estrelas',
                                '5' => '5 estrelas',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['reputacao_min'],
                            fn (Builder $query, $valor): Builder => $query->where('reputacao', '>=', $valor),
                        );
                    }),

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
                    ->action(function (SubConta $record) {
                        $record->update(['status_conta' => true]);
                        Notification::make()->title('Sub conta ativada!')->success()->send();
                    })
                    ->visible(fn (SubConta $record) => !$record->status_conta),

                Tables\Actions\Action::make('desativar')
                    ->label('Desativar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SubConta $record) {
                        $record->update(['status_conta' => false]);
                        Notification::make()->title('Sub conta desativada!')->success()->send();
                    })
                    ->visible(fn (SubConta $record) => $record->status_conta),

                Tables\Actions\Action::make('resetar_senha')
                    ->label('Resetar Senha')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('nova_senha')
                            ->label('Nova Senha')
                            ->password()
                            ->required()
                            ->minLength(6),
                    ])
                    ->action(function (SubConta $record, array $data) {
                        $record->update(['senha' => Hash::make($data['nova_senha'])]);
                        Notification::make()
                            ->title('Senha alterada!')
                            ->body('A senha foi redefinida com sucesso.')
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
                                $record->update(['status_conta' => true]);
                                $count++;
                            }
                            Notification::make()->title("{$count} sub contas ativadas!")->success()->send();
                        }),

                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['status_conta' => false]);
                                $count++;
                            }
                            Notification::make()->title("{$count} sub contas desativadas!")->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma sub conta encontrada')
            ->emptyStateDescription('Crie sub contas para funcionários ou parceiros vinculados às contas principais.')
            ->emptyStateIcon('heroicon-o-users');
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
            'index' => Pages\ListSubContas::route('/'),
            'create' => Pages\CreateSubConta::route('/create'),
            'view' => Pages\ViewSubConta::route('/{record}'),
            'edit' => Pages\EditSubConta::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_conta', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $ativas = static::getModel()::where('status_conta', true)->count();
        return $ativas > 0 ? 'success' : 'gray';
    }
}
