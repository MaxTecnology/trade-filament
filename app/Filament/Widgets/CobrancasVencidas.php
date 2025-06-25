<?php
// app/Filament/Widgets/CobrancasVencidas.php

namespace App\Filament\Widgets;

use App\Models\Cobranca;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CobrancasVencidas extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Cobranças Vencidas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cobranca::query()
                    ->where('vencimento_fatura', '<', now())
                    ->where('status', '!=', 'paga')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('referencia')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Referência'),

                Tables\Columns\TextColumn::make('usuario.nome')
                    ->label('Usuário')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('valor_fatura')
                    ->money('BRL')
                    ->sortable()
                    ->label('Valor'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paga' => 'success',
                        'pendente' => 'warning',
                        'vencida' => 'danger',
                        'cancelada' => 'gray',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('vencimento_fatura')
                    ->dateTime()
                    ->sortable()
                    ->color('danger')
                    ->label('Vencimento'),

                Tables\Columns\TextColumn::make('dias_vencido')
                    ->label('Dias Vencida')
                    ->getStateUsing(function ($record) {
                        return now()->diffInDays($record->vencimento_fatura) . ' dias';
                    })
                    ->badge()
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('marcar_paga')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'paga']))
                    ->requiresConfirmation(),
            ]);
    }
}
