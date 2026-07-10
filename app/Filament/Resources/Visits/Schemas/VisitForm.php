<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\PaymentType;
use App\Models\Certificate;
use App\Models\Promotion;
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
                        ->helperText('От неё считается зарплата мастера и списание с сертификата')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->suffix('р'),
                ]),

            Section::make('Скидка / особые условия')
                ->description('Необязательно. Для обычного заказа оставьте выключенными.')
                ->columns(2)
                ->schema([
                    Toggle::make('use_promotion')
                        ->label('Акция')
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                            // Выключили акцию — сбрасываем выбор и возвращаем базовую цену.
                            if (! $state) {
                                $set('promotion_id', null);
                                $set('service_price', (float) $get('base_price'));
                            }
                        }),
                    Toggle::make('use_special')
                        ->label('Особые условия')
                        ->helperText('Бартер, себестоимость и т.п.')
                        ->live()
                        ->dehydrated(false),
                    Select::make('promotion_id')
                        ->label('Акция')
                        ->options(fn () => Promotion::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (Promotion $p) => [$p->id => $p->selectLabel()]))
                        ->native(false)
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('use_promotion'))
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            $promotion = $state ? Promotion::find($state) : null;
                            $base = (float) $get('base_price');
                            $set('service_price', $promotion ? $promotion->applyTo($base) : $base);
                        }),
                    Textarea::make('discount_reason')
                        ->label('Особые условия / причина')
                        ->rows(2)
                        ->visible(fn (Get $get): bool => (bool) $get('use_special'))
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
                        ->native(false)
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
