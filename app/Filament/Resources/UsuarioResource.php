<?php
// app/Filament/Resources/UsuarioResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\UsuarioResource\Pages;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UsuarioResource extends Resource
{
    protected static ?string $model = Usuario::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Usuários';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Dados do Usuário')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informações Básicas')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nome')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2)
                                            ->validationMessages([
                                                'required' => 'O nome é obrigatório.',
                                                'max' => 'O nome deve ter no máximo 255 caracteres.',
                                            ]),

                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('cpf')
                                            ->required()
                                            ->mask('999.999.999-99')
                                            ->maxLength(14),

                                        Forms\Components\TextInput::make('senha')
                                            ->password()
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Toggle::make('status_conta')
                                            ->default(true)
                                            ->label('Conta Ativa'),

                                        Forms\Components\TextInput::make('reputacao')
                                            ->numeric()
                                            ->default(0.00)
                                            ->step(0.01)
                                            ->suffix('/5.00'),
                                    ])
                                    ->columns(4),

                                Forms\Components\FileUpload::make('imagem')
                                    ->image()
                                    ->directory('usuarios')
                                    ->label('Foto de Perfil')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Dados da Empresa')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('razao_social')
                                            ->maxLength(255)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('nome_fantasia')
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('cnpj')
                                            ->mask('99.999.999/9999-99')
                                            ->maxLength(18),

                                        Forms\Components\TextInput::make('insc_estadual')
                                            ->maxLength(255)
                                            ->label('Inscrição Estadual'),

                                        Forms\Components\TextInput::make('insc_municipal')
                                            ->maxLength(255)
                                            ->label('Inscrição Municipal'),

                                        Forms\Components\Select::make('tipo')
                                            ->options([
                                                'pessoa_fisica' => 'Pessoa Física',
                                                'pessoa_juridica' => 'Pessoa Jurídica',
                                            ])
                                            ->default('pessoa_fisica'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Textarea::make('descricao')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->label('Descrição da Empresa'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Contato')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('nome_contato')
                                            ->maxLength(255)
                                            ->label('Nome do Contato'),

                                        Forms\Components\TextInput::make('telefone')
                                            ->tel()
                                            ->mask('(99) 9999-9999')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('celular')
                                            ->tel()
                                            ->mask('(99) 99999-9999')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email_contato')
                                            ->email()
                                            ->maxLength(255)
                                            ->label('Email de Contato'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('email_secundario')
                                            ->email()
                                            ->maxLength(255)
                                            ->label('Email Secundário')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('site')
                                            ->label('Website')
                                            ->maxLength(255)
                                            ->prefix('https://')
                                            ->placeholder('google.com.br')
                                            ->columnSpan(2)
                                            ->helperText('Digite apenas o domínio, ex: meusite.com.br')
                                        ,
                                    ])
                                    ->columns(4),
                            ]),

                        Forms\Components\Tabs\Tab::make('Endereço')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('cep')
                                            ->mask('99999-999')
                                            ->maxLength(9),

                                        Forms\Components\TextInput::make('logradouro')
                                            ->maxLength(255)
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('numero')
                                            ->numeric(),
                                    ])
                                    ->columns(4),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('complemento')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('bairro')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('cidade')
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
                                            ->searchable(),
                                    ])
                                    ->columns(4),

                                Forms\Components\TextInput::make('regiao')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Configurações')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('aceita_orcamento')
                                            ->default(true)
                                            ->label('Aceita Orçamento'),

                                        Forms\Components\Toggle::make('aceita_voucher')
                                            ->default(true)
                                            ->label('Aceita Voucher'),

                                        Forms\Components\Toggle::make('mostrar_no_site')
                                            ->default(true)
                                            ->label('Mostrar no Site'),

                                        Forms\Components\Toggle::make('bloqueado')
                                            ->default(false)
                                            ->label('Usuário Bloqueado'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('tipo_operacao')
                                            ->options([
                                                1 => 'Apenas Comprador',
                                                2 => 'Apenas Vendedor',
                                                3 => 'Comprador e Vendedor',
                                            ])
                                            ->required()
                                            ->default(3),

                                        Forms\Components\Select::make('categoria_id')
                                            ->relationship('categoria', 'nome_categoria')
                                            ->searchable()
                                            ->preload()
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) => $set('subcategoria_id', null))
                                            ->label('Categoria Principal'),

                                        Forms\Components\Select::make('subcategoria_id')
                                            ->relationship('subcategoria', 'nome_subcategoria',
                                                fn ($query, $get) => $query->where('categoria_id', $get('categoria_id')))
                                            ->searchable()
                                            ->preload()
                                            ->label('Subcategoria'),

                                        Forms\Components\TextInput::make('taxa_comissao_gerente')
                                            ->numeric()
                                            ->suffix('%')
                                            ->label('Taxa Comissão Gerente'),
                                    ])
                                    ->columns(4),
                            ]),

                        Forms\Components\Tabs\Tab::make('Hierarquia')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('usuario_criador_id')
                                            ->relationship('usuarioCriador', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->label('Usuário Criador')
                                            ->helperText('Usuário que criou este registro'),

                                        Forms\Components\Select::make('matriz_id')
                                            ->relationship('matriz', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->label('Matriz')
                                            ->helperText('Usuário matriz/superior na hierarquia'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->columns(1); // Uma coluna para dar mais espaço
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagem')
                    ->circular(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status_conta')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reputacao')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('categoria.nome_categoria')
                    ->label('Categoria')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status_conta')
                    ->label('Status da Conta'),
                Tables\Filters\TernaryFilter::make('bloqueado'),
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->relationship('categoria', 'nome_categoria')
                    ->label('Categoria'),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'SP' => 'São Paulo',
                        'RJ' => 'Rio de Janeiro',
                        'MG' => 'Minas Gerais',
                        // Adicionar outros estados conforme necessário
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Informações do Usuário')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Dados Pessoais')
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        // Layout com imagem maior e dados ao lado
                                        Infolists\Components\Grid::make([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 3,
                                            'lg' => 4,
                                            'xl' => 4,
                                        ])
                                            ->schema([
                                                // Coluna da imagem (ocupa 1 coluna)
                                                Infolists\Components\ImageEntry::make('imagem')
                                                    ->circular()
                                                    ->size(120)
                                                    ->defaultImageUrl(url('/images/default-avatar.png'))
                                                    ->columnSpan(1),

                                                // Coluna dos dados principais (ocupa 3 colunas)
                                                Infolists\Components\Grid::make(2)
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('nome')
                                                            ->label('Nome Completo')
                                                            ->size('lg')
                                                            ->weight('bold')
                                                            ->color('primary')
                                                            ->columnSpanFull(),

                                                        Infolists\Components\TextEntry::make('email')
                                                            ->label('Email Principal')
                                                            ->icon('heroicon-m-envelope')
                                                            ->copyable(),

                                                        Infolists\Components\TextEntry::make('cpf')
                                                            ->label('CPF')
                                                            ->icon('heroicon-m-identification')
                                                            ->copyable(),

                                                        Infolists\Components\IconEntry::make('status_conta')
                                                            ->label('Status da Conta')
                                                            ->boolean()
                                                            ->trueIcon('heroicon-o-check-circle')
                                                            ->falseIcon('heroicon-o-x-circle')
                                                            ->trueColor('success')
                                                            ->falseColor('danger'),

                                                        Infolists\Components\TextEntry::make('reputacao')
                                                            ->label('Reputação')
                                                            ->suffix(' / 5.00')
                                                            ->badge()
                                                            ->color(fn ($state) => match (true) {
                                                                $state >= 4.0 => 'success',
                                                                $state >= 3.0 => 'warning',
                                                                $state >= 2.0 => 'danger',
                                                                default => 'gray',
                                                            }),
                                                    ])
                                                    ->columnSpan(3),
                                            ]),
                                    ])
                                    ->headerActions([
                                        Infolists\Components\Actions\Action::make('edit')
                                            ->label('Editar Usuário')
                                            ->icon('heroicon-m-pencil-square')
                                            ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Empresa')
                            ->schema([
                                Infolists\Components\Section::make('Dados da Empresa')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('tipo')
                                                    ->label('Tipo de Pessoa')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'pessoa_fisica' => 'info',
                                                        'pessoa_juridica' => 'success',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'pessoa_fisica' => 'Pessoa Física',
                                                        'pessoa_juridica' => 'Pessoa Jurídica',
                                                        default => $state,
                                                    }),

                                                Infolists\Components\TextEntry::make('razao_social')
                                                    ->label('Razão Social')
                                                    ->weight('bold'),

                                                Infolists\Components\TextEntry::make('nome_fantasia')
                                                    ->label('Nome Fantasia'),

                                                Infolists\Components\TextEntry::make('cnpj')
                                                    ->label('CNPJ')
                                                    ->copyable(),

                                                Infolists\Components\TextEntry::make('insc_estadual')
                                                    ->label('Inscrição Estadual'),

                                                Infolists\Components\TextEntry::make('insc_municipal')
                                                    ->label('Inscrição Municipal'),
                                            ]),

                                        Infolists\Components\TextEntry::make('descricao')
                                            ->label('Descrição da Empresa')
                                            ->columnSpanFull()
                                            ->prose(),
                                    ])
                                    ->columns(1),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Contato')
                            ->schema([
                                Infolists\Components\Section::make('Informações de Contato')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('nome_contato')
                                                    ->label('Nome do Contato')
                                                    ->icon('heroicon-m-user'),

                                                Infolists\Components\TextEntry::make('telefone')
                                                    ->label('Telefone')
                                                    ->icon('heroicon-m-phone'),

                                                Infolists\Components\TextEntry::make('celular')
                                                    ->label('Celular')
                                                    ->icon('heroicon-m-device-phone-mobile'),

                                                Infolists\Components\TextEntry::make('email_contato')
                                                    ->label('Email de Contato')
                                                    ->icon('heroicon-m-envelope')
                                                    ->copyable(),

                                                Infolists\Components\TextEntry::make('email_secundario')
                                                    ->label('Email Secundário')
                                                    ->icon('heroicon-m-envelope')
                                                    ->copyable(),

                                                Infolists\Components\TextEntry::make('site')
                                                    ->label('Website')
                                                    ->icon('heroicon-m-globe-alt')
                                                    ->url(fn ($state) => $state)
                                                    ->openUrlInNewTab(),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Endereço')
                            ->schema([
                                Infolists\Components\Section::make('Endereço Completo')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('cep')
                                                    ->label('CEP')
                                                    ->icon('heroicon-m-map-pin'),

                                                Infolists\Components\TextEntry::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->columnSpan(2),

                                                Infolists\Components\TextEntry::make('numero')
                                                    ->label('Número'),

                                                Infolists\Components\TextEntry::make('complemento')
                                                    ->label('Complemento'),

                                                Infolists\Components\TextEntry::make('bairro')
                                                    ->label('Bairro'),

                                                Infolists\Components\TextEntry::make('cidade')
                                                    ->label('Cidade'),

                                                Infolists\Components\TextEntry::make('estado')
                                                    ->label('Estado')
                                                    ->badge()
                                                    ->color('primary'),
                                            ]),

                                        Infolists\Components\TextEntry::make('regiao')
                                            ->label('Região')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Configurações')
                            ->schema([
                                Infolists\Components\Section::make('Configurações do Sistema')
                                    ->schema([
                                        Infolists\Components\Grid::make(4)
                                            ->schema([
                                                Infolists\Components\IconEntry::make('aceita_orcamento')
                                                    ->label('Aceita Orçamento')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-check-circle')
                                                    ->falseIcon('heroicon-o-x-circle')
                                                    ->trueColor('success')
                                                    ->falseColor('danger'),

                                                Infolists\Components\IconEntry::make('aceita_voucher')
                                                    ->label('Aceita Voucher')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-check-circle')
                                                    ->falseIcon('heroicon-o-x-circle')
                                                    ->trueColor('success')
                                                    ->falseColor('danger'),

                                                Infolists\Components\IconEntry::make('mostrar_no_site')
                                                    ->label('Mostrar no Site')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-check-circle')
                                                    ->falseIcon('heroicon-o-x-circle')
                                                    ->trueColor('success')
                                                    ->falseColor('danger'),

                                                Infolists\Components\IconEntry::make('bloqueado')
                                                    ->label('Usuário Bloqueado')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-x-circle')
                                                    ->falseIcon('heroicon-o-check-circle')
                                                    ->trueColor('danger')
                                                    ->falseColor('success'),
                                            ]),
                                    ])
                                    ->columns(1),

                                Infolists\Components\Section::make('Categorização e Operações')
                                    ->schema([
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('tipo_operacao')
                                                    ->label('Tipo de Operação')
                                                    ->badge()
                                                    ->color('warning')
                                                    ->formatStateUsing(fn ($state): string => match ($state) {
                                                        1 => 'Apenas Comprador',
                                                        2 => 'Apenas Vendedor',
                                                        3 => 'Comprador e Vendedor',
                                                        default => 'N/A',
                                                    }),

                                                Infolists\Components\TextEntry::make('categoria.nome_categoria')
                                                    ->label('Categoria')
                                                    ->badge()
                                                    ->color('info'),

                                                Infolists\Components\TextEntry::make('subcategoria.nome_subcategoria')
                                                    ->label('Subcategoria')
                                                    ->badge()
                                                    ->color('primary'),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Histórico')
                            ->schema([
                                Infolists\Components\Section::make('Informações do Sistema')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('usuarioCriador.nome')
                                                    ->label('Criado por')
                                                    ->default('Sistema'),

                                                Infolists\Components\TextEntry::make('matriz.nome')
                                                    ->label('Matriz')
                                                    ->default('Nenhuma'),

                                                Infolists\Components\TextEntry::make('created_at')
                                                    ->label('Data de Cadastro')
                                                    ->dateTime('d/m/Y H:i:s')
                                                    ->icon('heroicon-m-calendar'),

                                                Infolists\Components\TextEntry::make('updated_at')
                                                    ->label('Última Atualização')
                                                    ->dateTime('d/m/Y H:i:s')
                                                    ->icon('heroicon-m-clock'),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UsuarioResource\RelationManagers\OfertasRelationManager::class,
            UsuarioResource\RelationManagers\TransacoesCompradorRelationManager::class,
            UsuarioResource\RelationManagers\TransacoesVendedorRelationManager::class,
            UsuarioResource\RelationManagers\CobrancasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsuarios::route('/'),
            'create' => Pages\CreateUsuario::route('/create'),
            //'view' => Pages\ViewUsuario::route('/{record}'),
            'edit' => Pages\EditUsuario::route('/{record}/edit'),
        ];
    }
}
