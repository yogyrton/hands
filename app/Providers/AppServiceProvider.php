<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // В проде сайт всегда за HTTPS: canonical и og:url не должны отдавать http://
        // (частая причина расщепления сигналов индексации за TLS-прокси).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // В БД время хранится в UTC, а в админке показываем/принимаем в местном
        // поясе (Могилёв, UTC+3). Filament сам переводит колонки/поля к нему.
        FilamentTimezone::set(config('app.display_timezone'));
    }
}
