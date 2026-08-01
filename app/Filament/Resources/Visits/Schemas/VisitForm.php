<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Models\Certificate;
use App\Models\Promotion;
use App\Models\Service;
use Closure;
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
                        ->suffix('р')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                            // «Итоговая» по умолчанию равна базовой (при выбранной акции — со скидкой),
                            // чтобы оператор не забыл поменять её вслед за базовой. Ручную правку
                            // «Итоговой» после этого никто не отменяет.
                            $base = (float) $state;
                            $promotion = ($get('use_promotion') && $get('promotion_id'))
                                ? Promotion::find($get('promotion_id'))
                                : null;
                            $set('service_price', $promotion ? $promotion->applyTo($base) : $base);
                        }),
                    TextInput::make('service_price')
                        ->label('Итоговая стоимость')
                        ->helperText('От неё считается зарплата мастера и списание с сертификата. Меняется вслед за базовой; при ручной скидке можно переписать')
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
                    TextInput::make('special_paid_amount')
                        ->label('Сумма оплаты по кассе')
                        ->helperText('Сколько реально пробить по кассе (пойдёт в выручку и налог). Зарплата мастера всё равно считается от «Итоговой стоимости». Пусто — оплата равна итоговой.')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('р')
                        // Значение уходит в учёт только при включённых особых условиях.
                        ->visible(fn (Get $get): bool => (bool) $get('use_special') && ! (bool) $get('use_certificate') && ! (bool) $get('use_external_certificate'))
                        ->dehydrated(fn (Get $get): bool => (bool) $get('use_special') && ! (bool) $get('use_certificate') && ! (bool) $get('use_external_certificate')),
                ]),

            Section::make('Оплата')
                ->columns(2)
                ->schema([
                    Toggle::make('use_certificate')
                        ->label('Оплата сертификатом')
                        ->live()
                        ->dehydrated(false)
                        // Оплату сертификатом при редактировании не меняем (правится удалением+созданием).
                        ->hiddenOn('edit')
                        ->afterStateUpdated(function (bool $state, Set $set): void {
                            if ($state) {
                                $set('use_external_certificate', false);
                            }
                        })
                        ->columnSpanFull(),
                    Toggle::make('use_external_certificate')
                        ->label('Оплата старым сертификатом (из Excel)')
                        ->helperText('Старый бумажный сертификат, которого нет в базе — номер вводится вручную.')
                        ->live()
                        ->dehydrated(false)
                        ->hiddenOn('edit')
                        ->afterStateUpdated(function (bool $state, Set $set): void {
                            if ($state) {
                                $set('use_certificate', false);
                            }
                        })
                        ->columnSpanFull(),
                    TextInput::make('external_certificate_number')
                        ->label('Номер старого сертификата')
                        ->helperText('По вашему Excel. Остаток/доплату/разбивку опишите в комментарии.')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => (bool) $get('use_external_certificate'))
                        ->required(fn (Get $get): bool => (bool) $get('use_external_certificate'))
                        ->dehydrated(fn (Get $get): bool => (bool) $get('use_external_certificate'))
                        ->columnSpanFull(),
                    Select::make('certificate_id')
                        ->label('Сертификат')
                        ->options(fn () => Certificate::usable()->get()->mapWithKeys(
                            fn (Certificate $c) => [$c->id => $c->selectLabel()]
                        ))
                        ->searchable()
                        // Поиск только по номеру сертификата.
                        ->getSearchResultsUsing(fn (string $search) => Certificate::usable()
                            ->where('number', 'like', '%'.$search.'%')
                            ->orderByDesc('id')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (Certificate $c) => [$c->id => $c->selectLabel()])
                            ->all())
                        ->getOptionLabelUsing(fn ($value): ?string => Certificate::find($value)?->selectLabel())
                        ->live()
                        ->visible(fn (Get $get): bool => (bool) $get('use_certificate'))
                        ->helperText(function (Get $get): string {
                            $cert = ($id = $get('certificate_id')) ? Certificate::find($id) : null;
                            if ($cert?->comment) {
                                return 'Описание: '.$cert->comment.'. Для серта на посещения впишите цену этого сеанса в «Итоговую стоимость».';
                            }

                            return 'В списке только активные и не истёкшие с остатком';
                        })
                        ->rule(function (Get $get): Closure {
                            // Денежный серт не покрывает услугу, а доплата не включена — не даём сохранить.
                            return function (string $attribute, $value, Closure $fail) use ($get): void {
                                if (! $value || ! $get('use_certificate') || $get('use_surcharge')) {
                                    return;
                                }
                                $cert = Certificate::find($value);
                                if ($cert?->type === CertificateType::Money
                                    && (float) $get('service_price') > (float) $cert->remaining_amount) {
                                    $fail('Итоговая больше остатка по сертификату ('.number_format((float) $cert->remaining_amount, 2, '.', ' ').' р). Включите «Доплатить деньгами».');
                                }
                            };
                        })
                        ->columnSpanFull(),
                    Toggle::make('use_surcharge')
                        ->label('Доплатить деньгами')
                        ->live()
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => (bool) $get('use_external_certificate')
                            || ((bool) $get('use_certificate')
                                && Certificate::find($get('certificate_id'))?->type === CertificateType::Money))
                        ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                            if (! $state) {
                                $set('surcharge_amount', 0);

                                return;
                            }
                            // Подсказка суммы доплаты — только для серта из БД
                            // (у старого остаток неизвестен, вводится вручную).
                            if ($get('use_certificate')) {
                                $remaining = (float) (Certificate::find($get('certificate_id'))?->remaining_amount ?? 0);
                                $set('surcharge_amount', max(0, round((float) $get('service_price') - $remaining, 2)));
                            }
                        }),
                    Select::make('surcharge_payment_type')
                        ->label('Доплата — тип оплаты')
                        ->options(PaymentType::moneyOptions())
                        ->native(false)
                        ->default(PaymentType::Cash->value)
                        ->visible(fn (Get $get): bool => ((bool) $get('use_certificate') || (bool) $get('use_external_certificate')) && (bool) $get('use_surcharge')),
                    TextInput::make('surcharge_amount')
                        ->label('Сумма доплаты')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->suffix('р')
                        ->visible(fn (Get $get): bool => ((bool) $get('use_certificate') || (bool) $get('use_external_certificate')) && (bool) $get('use_surcharge'))
                        ->rule(function (Get $get): Closure {
                            // Сертификат + доплата обязаны в сумме закрыть итоговую (не меньше и не больше).
                            return function (string $attribute, $value, Closure $fail) use ($get): void {
                                if (! $get('use_certificate') || ! $get('use_surcharge')) {
                                    return;
                                }
                                $cert = Certificate::find($get('certificate_id'));
                                if ($cert?->type !== CertificateType::Money) {
                                    return;
                                }
                                $price = round((float) $get('service_price'), 2);
                                $surcharge = round((float) $value, 2);
                                $remaining = round((float) $cert->remaining_amount, 2);

                                if ($surcharge > $price) {
                                    $fail('Доплата больше итоговой стоимости.');
                                } elseif (round($price - $surcharge, 2) > $remaining) {
                                    // Сертификатом нельзя списать больше остатка — доплаты не хватает.
                                    $need = round($price - $remaining, 2);
                                    $fail('Доплаты мало: сертификат покроет только '.number_format($remaining, 2, '.', ' ').' р. Доплатите минимум '.number_format($need, 2, '.', ' ').' р.');
                                }
                            };
                        }),
                    Select::make('payment_type')
                        ->label('Тип оплаты (деньгами)')
                        ->options(PaymentType::moneyOptions())
                        ->native(false)
                        ->default(PaymentType::Cash->value)
                        ->visible(fn (Get $get): bool => ! (bool) $get('use_certificate') && ! (bool) $get('use_external_certificate'))
                        ->helperText('Обычная оплата без сертификата'),
                    Textarea::make('comment')
                        ->label('Комментарий')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
