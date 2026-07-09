<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('№')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (CertificateType $state): string => $state->label()),
                TextColumn::make('remaining')
                    ->label('Остаток')
                    ->state(fn (Certificate $record): string => $record->remainingLabel()),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (CertificateStatus $state): string => $state->label())
                    ->color(fn (CertificateStatus $state): string => $state->color()),
                TextColumn::make('sold_at')
                    ->label('Продан')
                    ->date('d.m.Y'),
                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->date('d.m.Y'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(CertificateStatus::options()),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(CertificateType::options()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
