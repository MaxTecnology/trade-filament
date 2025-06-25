<?php

namespace App\Filament\Resources\CategoriaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsuariosRelationManager extends RelationManager
{
    protected static string $relationship = 'usuarios';

    protected static ?string $title = 'Usuários da Categoria';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Usuário')
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

                        Forms\Components\Select::make('tipo')
                            ->options([
                                'pessoa_fisica' => 'Pessoa Física',
                                'pessoa_juridica' => 'Pessoa Jurídica',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Empresa (se PJ)')
                    ->schema([
                        Forms\Components\TextInput::make('razao_social')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nome_fantasia')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cnpj')
                            ->mask('99.999.999/9999-99')
                            ->maxLength(18),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('tipo') === 'pessoa_juridica'),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Forms\Components\Select::make('subcategoria_id')
                            ->relationship('subcategoria', 'nome_subcategoria',
                                fn ($query) => $query->where('categoria_id', $this->ownerRecord->id))
                            ->searchable()
                            ->preload()
                            ->label('Subcategoria'),

                        Forms\Components\Select::make('tipo_operacao')
                            ->options([
                                1 => 'Comprador',
                                2 => 'Vendedor',
                                3 => 'Ambos',
                            ])
                            ->required(),

                        Forms\Components\Toggle::make('aceita_orcamento')
                            ->default(true),

                        Forms\Components\Toggle::make('aceita_voucher')
                            ->default(true),

                        Forms\Components\Toggle::make('status_conta')
                            ->default(true)
                            ->label('Conta Ativa'),

                        Forms\Components\Toggle::make('mostrar_no_site')
                            ->default(true),
                    ])
                    ->columns(3),
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

                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pessoa_fisica' => 'info',
                        'pessoa_juridica' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pessoa_fisica' => 'PF',
                        'pessoa_juridica' => 'PJ',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('subcategoria.nome_subcategoria')
                    ->label('Subcategoria')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tipo_operacao')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        1 => 'Comprador',
                        2 => 'Vendedor',
                        3 => 'Ambos',
                        default => 'N/A',
                    }),

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

                Tables\Columns\TextColumn::make('cidade')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estado')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Cadastrado em'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'pessoa_fisica' => 'Pessoa Física',
                        'pessoa_juridica' => 'Pessoa Jurídica',
                    ]),

                Tables\Filters\SelectFilter::make('subcategoria_id')
                    ->relationship('subcategoria', 'nome_subcategoria')
                    ->label('Subcategoria'),

                Tables\Filters\SelectFilter::make('tipo_operacao')
                    ->options([
                        1 => 'Comprador',
                        2 => 'Vendedor',
                        3 => 'Ambos',
                    ]),

                Tables\Filters\TernaryFilter::make('status_conta')
                    ->label('Conta Ativa'),

                Tables\Filters\TernaryFilter::make('aceita_orcamento')
                    ->label('Aceita Orçamento'),

                Tables\Filters\TernaryFilter::make('aceita_voucher')
                    ->label('Aceita Voucher'),

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

                Tables\Filters\Filter::make('reputacao')
                    ->form([
                        Forms\Components\TextInput::make('reputacao_min')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['reputacao_min'],
                            fn ($query, $value) => $query->where('reputacao', '>=', $value));
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['categoria_id'] = $this->ownerRecord->id;
                        $data['senha'] = bcrypt('123456'); // Senha padrão
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('ativar_contas')
                        ->label('Ativar Contas')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status_conta' => true])))
                        ->color('success'),
                    Tables\Actions\BulkAction::make('desativar_contas')
                        ->label('Desativar Contas')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status_conta' => false])))
                        ->color('danger')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhum usuário encontrado')
            ->emptyStateDescription('Esta categoria ainda não possui usuários cadastrados.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
