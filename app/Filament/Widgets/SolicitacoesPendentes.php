<?php
// app/Filament/Widgets/SolicitacoesPendentes.php

namespace App\Filament\Widgets;

use App\Models\SolicitacaoCredito;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SolicitacoesPendentes extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Solicitações de Crédito Pendentes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SolicitacaoCredito::query()
                    ->where('status', 'Pendente')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('usuarioSolicitante.nome')
                    ->label('Solicitante')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('valor_solicitado')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('usuarioCriador.nome')
                    ->label('Criado por')
                    ->limit(20),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Solicitado em'),

                Tables\Columns\TextColumn::make('dias_pendente')
                    ->label('Dias Pendente')
                    ->getStateUsing(function ($record) {
                        return now()->diffInDays($record->created_at) . ' dias';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '0') ? 'success' :
                        (str_contains($state, '1') || str_contains($state, '2') ? 'warning' : 'danger')),
            ])
            ->actions([
                Tables\Actions\Action::make('aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['status' => 'Aprovado']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('negar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['status' => 'Negado']))
                    ->requiresConfirmation(),
            ]);
    }
}
