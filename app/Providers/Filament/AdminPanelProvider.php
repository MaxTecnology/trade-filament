<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => [
                    50 => '245, 243, 255',
                    100 => '237, 233, 254',
                    200 => '221, 214, 254',
                    300 => '196, 181, 253',
                    400 => '167, 139, 250',
                    500 => '139, 92, 246',
                    600 => '124, 58, 237',
                    700 => '109, 40, 217',
                    800 => '91, 33, 182',
                    900 => '76, 29, 149',
                    950 => '46, 16, 101',
                ],
                'secondary' => [
                    50 => '255, 247, 237',
                    100 => '255, 237, 213',
                    200 => '254, 215, 170',
                    300 => '253, 186, 116',
                    400 => '251, 146, 60',
                    500 => '249, 115, 22',
                    600 => '234, 88, 12',
                    700 => '194, 65, 12',
                    800 => '154, 52, 18',
                    900 => '124, 45, 18',
                    950 => '67, 20, 7',
                ],
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->brandName('RedeTrade')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/logo.png'))
            ->font('Inter')
            // ADICIONAR SHIELD PLUGIN
            ->plugin(\BezhanSalleh\FilamentShield\FilamentShieldPlugin::make())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
