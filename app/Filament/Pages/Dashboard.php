<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Visits\VisitResource;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Инфопанель — только для администратора (там финансы студии). Мастер её не
 * видит в меню и при заходе на «/admin» сразу перебрасывается в «Посещения».
 */
class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        // canAccess не трогаем (иначе мастер получил бы 403 на «/admin»);
        // вместо этого мягко уводим не-админа в учёт.
        if (! auth()->user()?->isAdmin()) {
            $this->redirect(VisitResource::getUrl());
        }
    }
}
