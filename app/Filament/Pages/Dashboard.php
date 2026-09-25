<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Visits\VisitResource;
use App\Support\DatabaseBackup;
use App\Support\DatabaseImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

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

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupDatabase')
                ->label('Бэкап БД')
                ->icon(Heroicon::OutlinedCircleStack)
                ->color('gray')
                ->url(route('admin.db-backup'))
                ->openUrlInNewTab(),

            Action::make('importDatabase')
                ->label('Импорт БД')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('danger')
                ->modalHeading('Импорт базы данных')
                ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                ->modalIconColor('danger')
                ->modalDescription('Загруженный файл ПОЛНОСТЬЮ заменит текущую базу — все нынешние данные будут стёрты. Перед импортом автоматически сохранится копия текущей базы (на случай отката).')
                ->modalSubmitActionLabel('Заменить базу')
                ->schema([
                    FileUpload::make('file')
                        ->label('Файл бэкапа (.sql)')
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $stored = is_array($data['file']) ? (string) reset($data['file']) : (string) $data['file'];
                    $sql = Storage::disk('local')->get($stored);

                    $import = app(DatabaseImport::class);

                    if ($sql === null || ! $import->looksLikeBackup($sql)) {
                        Storage::disk('local')->delete($stored);
                        Notification::make()
                            ->title('Не похоже на файл бэкапа')
                            ->body('В файле нет структуры таблиц. Импорт отменён, база не тронута.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Страховочная копия текущей базы перед заменой.
                    $safety = 'backups/before-import-'.now(config('app.display_timezone'))->format('Y-m-d_H-i').'.sql';
                    Storage::disk('local')->put($safety, app(DatabaseBackup::class)->dump());

                    $import->import($sql);
                    Storage::disk('local')->delete($stored);

                    Notification::make()
                        ->title('База импортирована')
                        ->body('Прежняя база сохранена как '.$safety.' — если что-то не так, можно откатиться.')
                        ->success()
                        ->send();
                }),
        ];
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
