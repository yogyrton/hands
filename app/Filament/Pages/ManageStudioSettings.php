<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

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

    /**
     * Значения по умолчанию для расходов и ставок взносов/налога.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        'expense_rent' => '1880',
        'expense_utilities' => '200',
        'expense_accountant' => '250',
        'contrib_fszn_percent' => '34',
        'contrib_belgosstrakh_percent' => '0.6',
        'income_tax_percent' => '20',
    ];

    public function mount(): void
    {
        $stored = Setting::allKeyed();
        // Непустые сохранённые значения побеждают дефолты; пустые — заполняем дефолтами,
        // чтобы поля не были пустыми и при сохранении легли реальные числа.
        $nonEmpty = array_filter($stored, static fn ($v): bool => $v !== null && $v !== '');

        $this->form->fill(array_merge($stored, self::DEFAULTS, $nonEmpty));
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
                    TextInput::make('yandex_maps_url')
                        ->label('Ссылка на карточку в Яндекс.Картах')
                        ->helperText('Открывается при клике на адрес (футер, блок «О студии»). Например: https://yandex.by/maps/org/hands/160529978134/')
                        ->url(),
                ]),

            Section::make('Реквизиты (ИП)')
                ->description('Показываются в футере (требование законодательства РБ)')
                ->columns(2)
                ->schema([
                    TextInput::make('legal_name')
                        ->label('Наименование / ФИО')
                        ->placeholder('ИП Иванов Иван Иванович'),
                    TextInput::make('legal_unp')
                        ->label('УНП'),
                    TextInput::make('legal_reg_authority')
                        ->label('Орган регистрации')
                        ->placeholder('Оршанский райисполком'),
                    TextInput::make('legal_reg_date')
                        ->label('Дата регистрации')
                        ->placeholder('17.06.2026'),
                    TextInput::make('legal_address')
                        ->label('Юридический адрес')
                        ->placeholder('Витебская обл., г. Орша, ул. …')
                        ->columnSpanFull(),
                    TextInput::make('work_hours')
                        ->label('Режим работы')
                        ->placeholder('Ежедневно с 9:00 до 21:00'),
                    TextInput::make('legal_email')
                        ->label('E-mail оператора')
                        ->email(),
                ]),

            Section::make('Расходы и взносы')
                ->description('Значения по умолчанию для авто-создания месяца расходов и ставки взносов/налога. Проценты уточните у бухгалтера — закон меняется.')
                ->columns(3)
                ->schema([
                    TextInput::make('expense_rent')
                        ->label('Аренда, р')
                        ->numeric()
                        ->placeholder('1880'),
                    TextInput::make('expense_utilities')
                        ->label('Квартплата, р')
                        ->numeric()
                        ->placeholder('200'),
                    TextInput::make('expense_accountant')
                        ->label('Услуги бухгалтера, р')
                        ->numeric()
                        ->placeholder('250'),
                    TextInput::make('contrib_fszn_percent')
                        ->label('ФСЗН (наниматель), %')
                        ->numeric()
                        ->placeholder('34'),
                    TextInput::make('contrib_belgosstrakh_percent')
                        ->label('Белгосстрах, %')
                        ->numeric()
                        ->placeholder('0.6'),
                    TextInput::make('income_tax_percent')
                        ->label('Подоходный налог, %')
                        ->numeric()
                        ->placeholder('20'),
                ]),

            Section::make('Оплата')
                ->description('Образец документа об оплате — ссылка появится в футере в разделе «Документы»')
                ->schema([
                    FileUpload::make('payment_receipt')
                        ->label('Образец документа об оплате (чек)')
                        ->disk('public')
                        ->directory('legal')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable(),
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
            // FileUpload может отдать массив [uuid => path] — берём путь.
            if (is_array($value)) {
                $value = reset($value) ?: '';
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
