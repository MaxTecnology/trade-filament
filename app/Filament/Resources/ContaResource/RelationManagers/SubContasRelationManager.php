<?php

namespace App\Filament\Resources\ContaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class SubContasRelationManager extends RelationManager
{
    protected static string $relationship = 'subContas';

    protected static ?string $title = 'Sub Contas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cpf')
                            ->required()
                            ->mask('999.999.999-99')
                            ->unique(ignoreRecord: true)
                            ->maxLength(14),

                        Forms\Components\TextInput::make('numero_sub_conta')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Número da Sub Conta')
                            ->helperText('Número único para identificar a sub conta'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Acesso e Segurança')
                    ->schema([
                        Forms\Components\TextInput::make('senha')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->label('Senha'),

                        Forms\Components\Toggle::make('status_conta')
                            ->default(true)
                            ->label('Conta Ativa'),

                        Forms\Components\TextInput::make('reputacao')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(0.00)
                            ->suffix('/5.00'),

                        Forms\Components\FileUpload::make('imagem')
                            ->image()
                            ->directory('sub-contas')
                            ->label('Foto de Perfil'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contato')
                    ->schema([
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
                            ->label('Email Alternativo'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('logradouro')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('numero')
                            ->numeric(),

                        Forms\Components\TextInput::make('cep')
                            ->mask('99999-999')
                            ->maxLength(9),

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
                            ]),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Permissões')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissoes')
                            ->options([
                                'comprar' => 'Comprar',
                                'vender' => 'Vender',
                                'transferir' => 'Transferir',
                                'visualizar_relatorios' => 'Visualizar Relatórios',
                                'gerenciar_ofertas' => 'Gerenciar Ofertas',
                                'emitir_cobrancas' => 'Emitir Cobranças',
                                'aprovar_transacoes' => 'Aprovar Transações',
                                'visualizar_financeiro' => 'Visualizar Financeiro',
                            ])
                            ->columns(2)
                            ->default(['comprar', 'vender'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                Tables\Columns\ImageColumn::make('imagem')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('numero_sub_conta')
                    ->label('Número')
                    ->copyable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('cpf')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('status_conta')
                    ->boolean()
                    ->label('Ativa'),

                Tables\Columns\TextColumn::make('reputacao')
                    ->numeric(2)
                    ->suffix('/5.00')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 4.0 => 'success',
                        $state >= 3.0 => 'warning',
                        $state >= 2.0 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('celular')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cidade')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('permissoes')
                    ->badge()
                    ->separator(',')
                    ->limit(2)
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status_conta')
                    ->label('Status da Conta'),

                Tables\Filters\Filter::make('reputacao')
                    ->form([
                        Forms\Components\TextInput::make('reputacao_min')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5),
                        Forms\Components\TextInput::make('reputacao_max')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['reputacao_min'], fn ($query, $value) => $query->where('reputacao', '>=', $value))
                            ->when($data['reputacao_max'], fn ($query, $value) => $query->where('reputacao', '<=', $value));
                    }),

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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['conta_pai_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status_conta' => true]))
                    ->visible(fn ($record) => !$record->status_conta),
                Tables\Actions\Action::make('desativar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['status_conta' => false]))
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status_conta),
                Tables\Actions\Action::make('reset_senha')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('nova_senha')
                            ->password()
                            ->required()
                            ->minLength(6)
                            ->label('Nova Senha'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'senha' => Hash::make($data['nova_senha'])
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('ativar')
                        ->label('Ativar Selecionadas')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status_conta' => true])))
                        ->color('success'),
                    Tables\Actions\BulkAction::make('desativar')
                        ->label('Desativar Selecionadas')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status_conta' => false])))
                        ->color('danger')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma sub conta encontrada')
            ->emptyStateDescription('Esta conta ainda não possui sub contas cadastradas.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
