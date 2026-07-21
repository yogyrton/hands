<?php

namespace App\Filament\Resources\Visits\Tables;

use App\Contracts\Services\VisitServiceInterface;
use App\Enums\PaymentType;
use App\Filament\Resources\Visits\VisitResource;
use App\Models\Visit;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('performed_at', 'desc')
            // Клик по строке открывает просмотр посещения (отдельная кнопка не нужна).
            ->recordUrl(fn (Visit $record): string => VisitResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('performed_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('master.name')
                    ->label('Мастер')
                    ->sortable(),
                TextColumn::make('service.name')
                    ->label('Услуга'),
                TextColumn::make('service_price')
                    ->label('Стоимость')
                    ->suffix(' р')
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Оплачено')
                    ->suffix(' р'),
                TextColumn::make('payment_type')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label()),
                TextColumn::make('certificate.number')
                    ->label('Сертификат')
                    ->prefix('№')
                    ->placeholder('—'),
                TextColumn::make('promotion.title')
                    ->label('Акция')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('master')
                    ->label('Мастер')
                    ->relationship('master', 'name'),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('performed_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('performed_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('delete')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Посещение будет удалено, а списание с сертификата — отменено.')
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->action(fn (Visit $record) => app(VisitServiceInterface::class)->deleteWithReversal($record)),
            ]);
    }
}
