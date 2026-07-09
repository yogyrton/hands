<?php

namespace App\Filament\Resources\SiteContents\Pages;

use App\Filament\Resources\SiteContents\SiteContentResource;
use App\Models\SiteContent;
use Filament\Resources\Pages\ListRecords;

class ListSiteContents extends ListRecords
{
    protected static string $resource = SiteContentResource::class;

    /**
     * Singleton: сразу открываем редактирование единственной записи.
     */
    public function mount(): void
    {
        $this->redirect(
            SiteContentResource::getUrl('edit', ['record' => SiteContent::current()->getKey()])
        );
    }
}
