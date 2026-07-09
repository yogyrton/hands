<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Enums\CertificateType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label('Тип сертификата')
                ->options(CertificateType::options())
                ->default(CertificateType::Visits->value)
                ->required()
                ->live(),
            TextInput::make('initial_visits')
                ->label('Количество посещений')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get): bool => $get('type') === CertificateType::Visits->value)
                ->required(fn (Get $get): bool => $get('type') === CertificateType::Visits->value),
            TextInput::make('initial_amount')
                ->label('Сумма')
                ->numeric()
                ->minValue(1)
                ->suffix('р')
                ->visible(fn (Get $get): bool => $get('type') === CertificateType::Money->value)
                ->required(fn (Get $get): bool => $get('type') === CertificateType::Money->value),
        ]);
    }
}
