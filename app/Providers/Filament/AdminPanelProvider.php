<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
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
            ->brandName('HANDS')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn (): HtmlString => new HtmlString(
                    '<a href="'.e(url('/')).'" target="_blank" rel="noopener noreferrer" '
                    .'title="Открыть сайт в новой вкладке" '
                    .'style="display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .75rem;'
                    .'margin-inline-end:.5rem;border-radius:.5rem;font-size:.875rem;font-weight:500;'
                    .'color:#b45309;background:#fef3c7;text-decoration:none;white-space:nowrap;">'
                    .'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" '
                    .'stroke-width="1.6" stroke="currentColor" style="width:1.1rem;height:1.1rem;">'
                    .'<path stroke-linecap="round" stroke-linejoin="round" '
                    .'d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0a8.95 8.95 0 0 0 4.5-1.2M12 21a8.95 '
                    .'8.95 0 0 1-4.5-1.2m9-15.6A8.95 8.95 0 0 1 21 9m-9-6a8.95 8.95 0 0 0-4.5 1.2M3.6 '
                    .'9A8.95 8.95 0 0 1 7.5 4.2M3 12h18M12 3c2.2 2.3 3.4 5.6 3.4 9s-1.2 6.7-3.4 9c-2.2'
                    .'-2.3-3.4-5.6-3.4-9s1.2-6.7 3.4-9Z" /></svg>'
                    .'<span>На сайт</span></a>'
                ),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
