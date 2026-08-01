<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Enums\CertificateType;
use App\Models\Certificate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Сертификат')
                ->columns(2)
                ->schema([
                    TextInput::make('number')
                        ->label('Номер')
                        ->helperText('Пусто — присвоится автоматически. Можно задать вручную (напр. по старой нумерации).')
                        ->placeholder(fn (): string => (string) ((int) (Certificate::max('id') ?? 0) + 1))
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('type')
                        ->label('Тип сертификата')
                        ->options(CertificateType::options())
                        ->default(CertificateType::Visits->value)
                        ->required()
                        ->live()
                        // Тип/суммы задаются при выпуске и завязаны на историю списаний —
                        // при редактировании их не меняем (ошибочную сумму: удалить неиспользованный
                        // и выпустить заново).
                        ->disabledOn('edit'),
                    TextInput::make('initial_visits')
                        ->label('Количество посещений')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (Get $get): bool => $get('type') === CertificateType::Visits->value)
                        ->required(fn (Get $get): bool => $get('type') === CertificateType::Visits->value)
                        ->disabledOn('edit'),
                    TextInput::make('initial_amount')
                        ->label('Сумма')
                        ->helperText('Общая сумма сертификата (сколько заплатил клиент)')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('р')
                        ->required()
                        ->disabledOn('edit'),
                    Textarea::make('comment')
                        ->label('Описание')
                        ->helperText('Для «на посещения» укажите разбивку, напр.: 6 классика по 45 + 6 спина по 63')
                        ->rows(2)
                        ->columnSpanFull()
                        ->required(fn (Get $get): bool => $get('type') === CertificateType::Visits->value),
                    DatePicker::make('expires_at')
                        ->label('Действует до')
                        ->helperText('Можно продлить срок действия')
                        ->visibleOn('edit'),
                ]),

            Section::make('Клиент (необязательно)')
                ->columns(3)
                ->schema([
                    TextInput::make('client_last_name')->label('Фамилия'),
                    TextInput::make('client_first_name')->label('Имя'),
                    TextInput::make('client_phone')->label('Телефон')->tel(),
                ]),
        ]);
    }
}
