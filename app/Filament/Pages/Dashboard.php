<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            Widgets\AccountWidget::class,
            Widgets\FilamentInfoWidget::class,
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\LatestTransactions::class,
            \App\Filament\Widgets\CobrancasVencidas::class,
        ];
    }
}
