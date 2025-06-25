<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Vouchers';

    protected static ?string $modelLabel = 'Voucher';

    protected static ?string $pluralModelLabel = 'Vouchers';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Voucher')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('codigo')
                                    ->label('Código do Voucher')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(36)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'Ativo' => 'Ativo',
                                        'Usado' => 'Usado',
                                        'Cancelado' => 'Cancelado',
                                        'Expirado' => 'Expirado',
                                    ])
                                    ->required()
                                    ->default('Ativo')
                                    ->native(false)
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('transacao_id')
                                    ->label('Transação')
                                    ->relationship('transacao', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\DateTimePicker::make('data_cancelamento')
                                    ->label('Data de Cancelamento')
                                    ->nullable()
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Código copiado!')
                    ->limit(20),

                Tables\Columns\TextColumn::make('transacao.id')
                    ->label('Transação #')
                    ->prefix('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transacao.comprador.nome')
                    ->label('Comprador')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('transacao.vendedor.nome')
                    ->label('Vendedor')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('transacao.valor_total')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Usado' => 'info',
                        'Cancelado' => 'danger',
                        'Expirado' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Ativo' => 'Ativo',
                        'Usado' => 'Usado',
                        'Cancelado' => 'Cancelado',
                        'Expirado' => 'Expirado',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('usar')
                    ->label('Usar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Voucher $record) {
                        $record->update(['status' => 'Usado']);

                        Notification::make()
                            ->title('Voucher marcado como usado!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Voucher $record) => $record->status === 'Ativo'),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Voucher $record) {
                        $record->update([
                            'status' => 'Cancelado',
                            'data_cancelamento' => now(),
                        ]);

                        Notification::make()
                            ->title('Voucher cancelado!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Voucher $record) => in_array($record->status, ['Ativo', 'Expirado'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum voucher encontrado')
            ->emptyStateDescription('Vouchers são gerados automaticamente quando transações são aprovadas.')
            ->emptyStateIcon('heroicon-o-ticket');
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
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'view' => Pages\ViewVoucher::route('/{record}'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'Ativo')->count();
    }
}
