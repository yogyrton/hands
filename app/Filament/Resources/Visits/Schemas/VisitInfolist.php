<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\PaymentType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VisitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Посещение')
                ->columns(3)
                ->schema([
                    TextEntry::make('performed_at')->label('Когда')->dateTime('d.m.Y H:i'),
                    TextEntry::make('master.name')->label('Мастер'),
                    TextEntry::make('service.name')->label('Услуга'),
                    TextEntry::make('base_price')->label('Базовая цена')->suffix(' р'),
                    TextEntry::make('service_price')->label('Итоговая стоимость')->suffix(' р'),
                    TextEntry::make('paid_amount')->label('Оплачено деньгами')->suffix(' р'),
                    TextEntry::make('payment_type')
                        ->label('Тип оплаты')
                        ->badge()
                        ->formatStateUsing(fn (PaymentType $state): string => $state->label()),
                    TextEntry::make('certificate.number')->label('Сертификат')->prefix('№')->placeholder('—'),
                    TextEntry::make('discount_reason')->label('Скидка / условия')->placeholder('—'),
                ]),

            Section::make('Комментарий')
                ->schema([
                    TextEntry::make('comment')->hiddenLabel()->placeholder('—'),
                ]),
        ]);
    }
}
