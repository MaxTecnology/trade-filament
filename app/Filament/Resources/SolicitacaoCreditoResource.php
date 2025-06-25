<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitacaoCreditoResource\Pages;
use App\Filament\Resources\SolicitacaoCreditoResource\RelationManagers;
use App\Models\SolicitacaoCredito;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class SolicitacaoCreditoResource extends Resource
{
    protected static ?string $model = SolicitacaoCredito::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Solicitação Créditos';

    protected static ?string $modelLabel = 'Solicitação de Crédito';

    protected static ?string $pluralModelLabel = 'Solicitações de Crédito';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Solicitação de Crédito')
                    ->tabs([

                        // ===== ABA: DADOS DA SOLICITAÇÃO =====
                        Forms\Components\Tabs\Tab::make('Dados da Solicitação')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Informações Básicas')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('usuario_solicitante_id')
                                                    ->label('Usuário Solicitante')
                                                    ->relationship('usuarioSolicitante', 'nome')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('valor_solicitado')
                                                    ->label('Valor Solicitado')
                                                    ->numeric()
                                                    ->prefix('R$')
                                                    ->required()
                                                    ->step(0.01)
                                                    ->minValue(0)
                                                    ->maxValue(999999.99)
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Textarea::make('descricao_solicitante')
                                            ->label('Descrição/Justificativa')
                                            ->required()
                                            ->rows(4)
                                            ->placeholder('Descreva o motivo da solicitação de crédito...')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'Pendente' => 'Pendente',
                                                'Em Análise' => 'Em Análise',
                                                'Aprovado' => 'Aprovado',
                                                'Negado' => 'Negado',
                                                'Cancelado' => 'Cancelado',
                                            ])
                                            ->required()
                                            ->default('Pendente')
                                            ->native(false)
                                            ->live(),
                                    ]),
                            ]),

                        // ===== ABA: ANÁLISE =====
                        Forms\Components\Tabs\Tab::make('Análise')
                            ->icon('heroicon-o-document-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('Análise da Agência')
                                    ->schema([
                                        Forms\Components\Textarea::make('comentario_agencia')
                                            ->label('Comentário da Agência')
                                            ->rows(3)
                                            ->placeholder('Comentários sobre a análise inicial...')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Aprovação da Matriz')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('matriz_id')
                                                    ->label('Responsável pela Aprovação')
                                                    ->relationship('matriz', 'nome')
                                                    ->searchable()
                                                    ->preload()
                                                    ->columnSpan(1),

                                                Forms\Components\Toggle::make('matriz_aprovacao')
                                                    ->label('Aprovação da Matriz')
                                                    ->helperText('Marque se a matriz aprovou a solicitação')
                                                    ->columnSpan(1),
                                            ]),

                                        Forms\Components\Textarea::make('comentario_matriz')
                                            ->label('Comentário da Matriz')
                                            ->rows(3)
                                            ->placeholder('Comentários da matriz sobre a aprovação...')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ===== ABA: REJEIÇÃO =====
                        Forms\Components\Tabs\Tab::make('Rejeição')
                            ->icon('heroicon-o-x-circle')
                            ->schema([
                                Forms\Components\Section::make('Motivo da Rejeição')
                                    ->description('Preencha apenas se a solicitação for negada')
                                    ->schema([
                                        Forms\Components\Textarea::make('motivo_rejeicao')
                                            ->label('Motivo da Rejeição')
                                            ->rows(4)
                                            ->placeholder('Descreva detalhadamente o motivo da rejeição...')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->visible(fn ($get) => $get('status') === 'Negado'),

                        // ===== ABA: HISTÓRICO =====
                        Forms\Components\Tabs\Tab::make('Histórico')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Section::make('Informações de Criação')
                                    ->schema([
                                        Forms\Components\Select::make('usuario_criador_id')
                                            ->label('Criado por')
                                            ->relationship('usuarioCriador', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Data de Criação')
                                            ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i:s') ?? 'Novo registro'),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Última Atualização')
                                            ->content(fn ($record) => $record?->updated_at?->format('d/m/Y H:i:s') ?? 'Não atualizado'),
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
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('usuarioSolicitante.nome')
                    ->label('Solicitante')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('valor_solicitado')
                    ->label('Valor Solicitado')
                    ->money('BRL')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendente' => 'warning',
                        'Em Análise' => 'info',
                        'Aprovado' => 'success',
                        'Negado' => 'danger',
                        'Cancelado' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Pendente' => 'heroicon-o-clock',
                        'Em Análise' => 'heroicon-o-magnifying-glass',
                        'Aprovado' => 'heroicon-o-check-circle',
                        'Negado' => 'heroicon-o-x-circle',
                        'Cancelado' => 'heroicon-o-minus-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('usuarioCriador.nome')
                    ->label('Criado por')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('matriz.nome')
                    ->label('Responsável')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('Não definido'),

                Tables\Columns\IconColumn::make('matriz_aprovacao')
                    ->label('Aprovação Matriz')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->placeholder('Pendente'),

                Tables\Columns\TextColumn::make('descricao_solicitante')
                    ->label('Descrição')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descricao_solicitante)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pendente' => 'Pendente',
                        'Em Análise' => 'Em Análise',
                        'Aprovado' => 'Aprovado',
                        'Negado' => 'Negado',
                        'Cancelado' => 'Cancelado',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('usuario_solicitante_id')
                    ->label('Solicitante')
                    ->relationship('usuarioSolicitante', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('matriz_id')
                    ->label('Responsável')
                    ->relationship('matriz', 'nome')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('matriz_aprovacao')
                    ->label('Aprovação da Matriz')
                    ->placeholder('Todos')
                    ->trueLabel('Aprovados')
                    ->falseLabel('Negados'),

                Tables\Filters\Filter::make('valor_range')
                    ->form([
                        Forms\Components\TextInput::make('valor_min')
                            ->label('Valor Mínimo')
                            ->numeric()
                            ->prefix('R$'),
                        Forms\Components\TextInput::make('valor_max')
                            ->label('Valor Máximo')
                            ->numeric()
                            ->prefix('R$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valor_min'],
                                fn (Builder $query, $valor): Builder => $query->where('valor_solicitado', '>=', $valor),
                            )
                            ->when(
                                $data['valor_max'],
                                fn (Builder $query, $valor): Builder => $query->where('valor_solicitado', '<=', $valor),
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

                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('comentario_aprovacao')
                            ->label('Comentário da Aprovação')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SolicitacaoCredito $record, array $data) {
                        $record->update([
                            'status' => 'Aprovado',
                            'matriz_aprovacao' => true,
                            'comentario_matriz' => $data['comentario_aprovacao'],
                        ]);

                        Notification::make()
                            ->title('Solicitação aprovada!')
                            ->body("Solicitação de R$ " . number_format($record->valor_solicitado, 2, ',', '.') . " foi aprovada.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => in_array($record->status, ['Pendente', 'Em Análise'])),

                Tables\Actions\Action::make('negar')
                    ->label('Negar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('motivo_rejeicao')
                            ->label('Motivo da Rejeição')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SolicitacaoCredito $record, array $data) {
                        $record->update([
                            'status' => 'Negado',
                            'matriz_aprovacao' => false,
                            'motivo_rejeicao' => $data['motivo_rejeicao'],
                        ]);

                        Notification::make()
                            ->title('Solicitação negada!')
                            ->body('A solicitação foi negada e o solicitante será notificado.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => in_array($record->status, ['Pendente', 'Em Análise'])),

                Tables\Actions\Action::make('em_analise')
                    ->label('Em Análise')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(function (SolicitacaoCredito $record) {
                        $record->update(['status' => 'Em Análise']);

                        Notification::make()
                            ->title('Status atualizado!')
                            ->body('Solicitação marcada como "Em Análise".')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => $record->status === 'Pendente'),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-minus-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (SolicitacaoCredito $record) {
                        $record->update(['status' => 'Cancelado']);

                        Notification::make()
                            ->title('Solicitação cancelada!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SolicitacaoCredito $record) => in_array($record->status, ['Pendente', 'Em Análise'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('marcar_em_analise')
                        ->label('Marcar como Em Análise')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'Pendente') {
                                    $record->update(['status' => 'Em Análise']);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} solicitações marcadas como 'Em Análise'!")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Selecionadas')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Exportação iniciada!')
                                ->body('As solicitações selecionadas serão processadas.')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma solicitação encontrada')
            ->emptyStateDescription('As solicitações de crédito aparecerão aqui quando forem criadas.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
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
            'index' => Pages\ListSolicitacaoCreditos::route('/'),
            'create' => Pages\CreateSolicitacaoCredito::route('/create'),
            'view' => Pages\ViewSolicitacaoCredito::route('/{record}'),
            'edit' => Pages\EditSolicitacaoCredito::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'Pendente')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendentes = static::getModel()::where('status', 'Pendente')->count();
        return $pendentes > 0 ? 'warning' : 'gray';
    }
}
