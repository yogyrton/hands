<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\PaymentType;
use App\Models\Certificate;
use App\Models\Service;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Оказанная услуга')
                ->columns(2)
                ->schema([
                    Select::make('master_id')
                        ->label('Мастер')
                        ->relationship('master', 'name', fn ($query) => $query->where('is_active', true))
                        ->native(false)
                        ->required(),
                    Select::make('service_id')
                        ->label('Услуга')
                        ->relationship('service', 'name', fn ($query) => $query->where('is_active', true))
                        ->native(false)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $service = $state ? Service::find($state) : null;
                            if ($service) {
                                $set('base_price', (float) $service->base_price);
                                $set('service_price', (float) $service->base_price);
                            }
                        }),
                    TextInput::make('base_price')
                        ->label('Базовая цена')
                        ->numeric()
                        ->default(0)
                        ->suffix('р'),
                    TextInput::make('service_price')
                        ->label('Итоговая стоимость')
                        ->helperText('От неё считается зарплата мастера')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->suffix('р'),
                    TextInput::make('discount_reason')
                        ->label('Скидка / особые условия')
                        ->placeholder('Напр.: ранняя пташка −10%')
                        ->columnSpanFull(),
                ]),

            Section::make('Оплата')
                ->columns(2)
                ->schema([
                    Toggle::make('use_certificate')
                        ->label('Оплата сертификатом')
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                    Select::make('certificate_id')
                        ->label('Сертификат')
                        ->options(fn () => Certificate::usable()->get()->mapWithKeys(
                            fn (Certificate $c) => [$c->id => $c->selectLabel()]
                        ))
                        ->searchable()
                        ->visible(fn (Get $get): bool => (bool) $get('use_certificate'))
                        ->helperText('В списке только активные и не истёкшие с остатком'),
                    Select::make('payment_type')
                        ->label('Тип оплаты (деньгами)')
                        ->options(PaymentType::moneyOptions())
                        ->default(PaymentType::Cash->value)
                        ->helperText('Для обычной оплаты и доплаты, если сертификата не хватает'),
                    Textarea::make('comment')
                        ->label('Комментарий')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
