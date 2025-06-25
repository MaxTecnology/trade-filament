<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CobrancaResource\Pages;
use App\Filament\Resources\CobrancaResource\RelationManagers;
use App\Models\Cobranca;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CobrancaResource extends Resource
{
    protected static ?string $model = Cobranca::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Cobrança';

    protected static ?string $pluralModelLabel = 'Cobranças';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Cobrança')
                    ->schema([
                        Forms\Components\TextInput::make('valor_fatura')
                            ->numeric()
                            ->required()
                            ->prefix('R$')
                            ->step(0.01)
                            ->label('Valor da Fatura'),

                        Forms\Components\TextInput::make('referencia')
                            ->required()
                            ->maxLength(255)
                            ->label('Referência')
                            ->helperText('Mês/Ano ou descrição da cobrança'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pendente' => 'Pendente',
                                'paga' => 'Paga',
                                'vencida' => 'Vencida',
                                'cancelada' => 'Cancelada',
                                'em_analise' => 'Em Análise',
                                'parcial' => 'Paga Parcialmente',
                            ])
                            ->required()
                            ->default('pendente'),

                        Forms\Components\DateTimePicker::make('vencimento_fatura')
                            ->label('Vencimento')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Relacionamentos')
                    ->schema([
                        Forms\Components\Select::make('usuario_id')
                            ->relationship('usuario', 'nome')
                            ->searchable()
                            ->preload()
                            ->label('Usuário'),

                        Forms\Components\Select::make('conta_id')
                            ->relationship('conta', 'numero_conta')
                            ->searchable()
                            ->preload()
                            ->label('Conta'),

                        Forms\Components\Select::make('sub_conta_id')
                            ->relationship('subConta', 'nome')
                            ->searchable()
                            ->preload()
                            ->label('Sub Conta'),

                        Forms\Components\Select::make('transacao_id')
                            ->relationship('transacao', 'codigo')
                            ->searchable()
                            ->preload()
                            ->label('Transação Relacionada'),

                        Forms\Components\Select::make('gerente_conta_id')
                            ->relationship('gerente', 'nome')
                            ->searchable()
                            ->preload()
                            ->label('Gerente Responsável'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('valor_fatura')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paga' => 'success',
                        'pendente' => 'warning',
                        'vencida' => 'danger',
                        'cancelada' => 'gray',
                        'em_analise' => 'info',
                        'parcial' => 'primary',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('conta.numero_conta')
                    ->label('Conta')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('vencimento_fatura')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->vencida ? 'danger' : 'success')
                    ->label('Vencimento'),

                Tables\Columns\TextColumn::make('gerente.nome')
                    ->label('Gerente')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pendente' => 'Pendente',
                        'paga' => 'Paga',
                        'vencida' => 'Vencida',
                        'cancelada' => 'Cancelada',
                        'em_analise' => 'Em Análise',
                        'parcial' => 'Paga Parcialmente',
                    ]),

                Tables\Filters\Filter::make('vencidas')
                    ->query(fn ($query) => $query->where('vencimento_fatura', '<', now()))
                    ->label('Vencidas'),

                Tables\Filters\Filter::make('a_vencer')
                    ->query(fn ($query) => $query->whereBetween('vencimento_fatura', [now(), now()->addDays(7)]))
                    ->label('Vencem em 7 dias'),

                Tables\Filters\Filter::make('vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_de')
                            ->label('Vencimento de'),
                        Forms\Components\DatePicker::make('vencimento_ate')
                            ->label('Vencimento até'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['vencimento_de'], fn ($query, $date) => $query->whereDate('vencimento_fatura', '>=', $date))
                            ->when($data['vencimento_ate'], fn ($query, $date) => $query->whereDate('vencimento_fatura', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('usuario_id')
                    ->relationship('usuario', 'nome')
                    ->label('Usuário')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('gerente_conta_id')
                    ->relationship('gerente', 'nome')
                    ->label('Gerente')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('marcar_paga')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Cobranca $record) => $record->update(['status' => 'paga']))
                    ->requiresConfirmation()
                    ->visible(fn (Cobranca $record) => $record->status !== 'paga'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('marcar_vencidas')
                        ->label('Marcar como Vencidas')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'vencida'])))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('vencimento_fatura', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações da Cobrança')
                    ->schema([
                        Infolists\Components\TextEntry::make('referencia')
                            ->size('lg')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('valor_fatura')
                            ->money('BRL')
                            ->size('lg')
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge(),
                        Infolists\Components\TextEntry::make('vencimento_fatura')
                            ->dateTime()
                            ->color(fn ($record) => $record->vencida ? 'danger' : 'success'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Relacionamentos')
                    ->schema([
                        Infolists\Components\TextEntry::make('usuario.nome')
                            ->label('Usuário'),
                        Infolists\Components\TextEntry::make('conta.numero_conta')
                            ->label('Número da Conta'),
                        Infolists\Components\TextEntry::make('subConta.nome')
                            ->label('Sub Conta'),
                        Infolists\Components\TextEntry::make('transacao.codigo')
                            ->label('Transação'),
                        Infolists\Components\TextEntry::make('gerente.nome')
                            ->label('Gerente'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Datas')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Criada em'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Atualizada em'),
                    ])
                    ->columns(2),
            ]);
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
            'index' => Pages\ListCobrancas::route('/'),
            'create' => Pages\CreateCobranca::route('/create'),
            //'view' => Pages\ViewCobranca::route('/{record}'),
            'edit' => Pages\EditCobranca::route('/{record}/edit'),
        ];
    }
}
