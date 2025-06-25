<?php

// app/Filament/Widgets/LatestTransactions.php

namespace App\Filament\Widgets;

use App\Models\Transacao;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTransactions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Últimas Transações';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transacao::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nome_comprador')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nome_vendedor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor_rt')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aprovada' => 'success',
                        'pendente' => 'warning',
                        'cancelada' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
