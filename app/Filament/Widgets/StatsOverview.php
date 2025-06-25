<?php
// app/Filament/Widgets/StatsOverview.php

namespace App\Filament\Widgets;

use App\Models\Usuario;
use App\Models\Oferta;
use App\Models\Transacao;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1; // Aparece primeiro

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Usuários', Usuario::count())
                ->description('Usuários cadastrados no sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Ofertas Ativas', Oferta::where('status', true)->count())
                ->description('Ofertas disponíveis no marketplace')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Transações', Transacao::count())
                ->description('Total de transações realizadas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Volume Total', 'R$ ' . number_format(
                    Transacao::sum('valor_rt') ?? 0, 2, ',', '.'
                ))
                ->description('Volume financeiro total')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
