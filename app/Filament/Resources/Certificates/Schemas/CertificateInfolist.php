<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Сертификат')
                ->columns(3)
                ->schema([
                    TextEntry::make('number')->label('№'),
                    TextEntry::make('type')
                        ->label('Тип')
                        ->formatStateUsing(fn (CertificateType $state): string => $state->label()),
                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn (CertificateStatus $state): string => $state->label())
                        ->color(fn (CertificateStatus $state): string => $state->color()),
                    TextEntry::make('remaining')
                        ->label('Остаток')
                        ->state(fn (Certificate $record): string => $record->remainingLabel()),
                    TextEntry::make('initial_amount')->label('Сумма')->suffix(' р')->placeholder('—'),
                    TextEntry::make('sold_at')->label('Продан')->date('d.m.Y'),
                    TextEntry::make('expires_at')->label('Действует до')->date('d.m.Y'),
                    TextEntry::make('comment')->label('Описание')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Клиент')
                ->columns(3)
                ->schema([
                    TextEntry::make('client')
                        ->label('ФИО')
                        ->state(fn (Certificate $record): string => $record->clientLabel()),
                    TextEntry::make('client_phone')->label('Телефон')->placeholder('—'),
                ]),
        ]);
    }
}
