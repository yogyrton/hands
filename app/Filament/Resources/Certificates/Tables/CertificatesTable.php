<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            // Клик по строке открывает сертификат; изменить/удалить — внутри карточки (админу).
            ->recordUrl(fn (Certificate $record): string => CertificateResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')
                    ->label('№')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client')
                    ->label('Клиент')
                    ->state(fn (Certificate $record): string => $record->clientLabel())
                    ->searchable(['client_last_name', 'client_first_name']),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (CertificateType $state): string => $state->label()),
                TextColumn::make('initial_amount')
                    ->label('Сумма')
                    ->suffix(' р')
                    ->placeholder('—'),
                TextColumn::make('remaining')
                    ->label('Остаток')
                    ->state(fn (Certificate $record): string => $record->remainingLabel()),
                // Статус по остатку: использован (остаток обнулён) или нет.
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (CertificateStatus $state): string => $state === CertificateStatus::Used ? 'Использован' : 'Неиспользован')
                    ->color(fn (CertificateStatus $state): string => $state === CertificateStatus::Used ? 'gray' : 'info'),
                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->date('d.m.Y'),
                // Состояние по сроку (живьём): активен / заканчивается / истёк.
                TextColumn::make('condition')
                    ->label('Состояние')
                    ->badge()
                    ->state(fn (Certificate $record): string => $record->conditionLabel())
                    ->color(fn (Certificate $record): string => $record->conditionColor()),
            ])
            ->filters([
                // Статус по остатку.
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'unused' => 'Неиспользован',
                        'used' => 'Использован',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'used' => $query->where('status', CertificateStatus::Used->value),
                            'unused' => $query->where('status', '!=', CertificateStatus::Used->value),
                            default => $query,
                        };
                    }),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(CertificateType::options()),
                // Состояние по сроку.
                SelectFilter::make('condition')
                    ->label('Состояние')
                    ->options([
                        'active' => 'Активен',
                        'ending' => 'Заканчивается (< месяца)',
                        'expired' => 'Истёк',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $today = now()->toDateString();
                        $monthAhead = now()->addMonth()->toDateString();

                        return match ($data['value'] ?? null) {
                            'expired' => $query->whereDate('expires_at', '<', $today),
                            'ending' => $query
                                ->whereDate('expires_at', '>=', $today)
                                ->whereDate('expires_at', '<=', $monthAhead),
                            'active' => $query->whereDate('expires_at', '>', $monthAhead),
                            default => $query,
                        };
                    }),
            ]);
    }
}
