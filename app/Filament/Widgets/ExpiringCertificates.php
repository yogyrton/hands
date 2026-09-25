<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Дашборд: сертификаты, у которых ещё есть остаток и срок действия истекает
 * в течение ближайшего месяца. Заголовок показывает их количество (бейдж),
 * клик по строке открывает сертификат. Только для администратора.
 */
class ExpiringCertificates extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return 'Истекают в течение месяца · '.$this->baseQuery()->count();
    }

    /**
     * @return Builder<Certificate>
     */
    protected function baseQuery(): Builder
    {
        // usable(): активные, с остатком, срок ещё не прошёл. Дополнительно —
        // ограничиваем ближайшим месяцем (те, что скоро сгорят).
        return Certificate::query()
            ->usable()
            ->whereDate('expires_at', '<=', now()->addMonth()->toDateString());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->baseQuery()->orderBy('expires_at'))
            ->recordUrl(fn (Certificate $record): string => CertificateResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Нет сертификатов, истекающих в ближайший месяц')
            ->paginated(false)
            ->columns([
                TextColumn::make('number')
                    ->label('№'),
                TextColumn::make('client')
                    ->label('Клиент')
                    ->state(fn (Certificate $record): string => $record->clientLabel()),
                TextColumn::make('remaining')
                    ->label('Остаток')
                    ->state(fn (Certificate $record): string => $record->remainingLabel()),
                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->date('d.m.Y')
                    ->color('danger'),
            ]);
    }
}
