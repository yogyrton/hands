<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageStudioSettings extends Page
{
    protected string $view = 'filament.pages.manage-studio-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 4;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Настройки студии';
    }

    public function getTitle(): string
    {
        return 'Настройки студии';
    }

    public function mount(): void
    {
        $this->form->fill(Setting::allKeyed());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ссылки и контакты')
                ->columns(2)
                ->schema([
                    TextInput::make('yclients_main')
                        ->label('YClients — общая ссылка')
                        ->url(),
                    TextInput::make('instagram_url')
                        ->label('Instagram')
                        ->url(),
                    TextInput::make('address')
                        ->label('Адрес'),
                    TextInput::make('phone')
                        ->label('Телефон'),
                    TextInput::make('gift_min_delivery')
                        ->label('Мин. сумма бесплатной доставки сертификата'),
                ]),

            Section::make('Карта')
                ->schema([
                    Textarea::make('yandex_map_embed')
                        ->label('URL встраивания Яндекс-карты')
                        ->rows(3),
                ]),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
