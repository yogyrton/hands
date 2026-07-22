<?php

namespace App\Filament\Resources\PayrollPeriods\RelationManagers;

use App\Models\Master;
use App\Models\MasterPayout;
use App\Models\PayrollPeriod;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'Мастера';

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('master_id')
                ->label('Мастер')
                ->options(fn (): array => Master::query()->orderBy('sort_order')->pluck('name', 'id')->all())
                ->required()
                ->disabledOn('edit'),
            Placeholder::make('accrued')
                ->label('Заработано / начислено')
                ->content(function (?MasterPayout $record): string {
                    if ($record === null) {
                        return '—';
                    }

                    return number_format($record->earned(), 2, '.', ' ').' р · начислено '
                        .number_format($record->accrued(), 2, '.', ' ').' р';
                })
                ->visibleOn('edit'),
            DatePicker::make('advance_date')
                ->label('Дата аванса')
                ->displayFormat('d.m.Y'),
            TextInput::make('advance_amount')
                ->label('Сумма аванса')
                ->numeric()
                ->minValue(0)
                ->suffix(' р'),
            DatePicker::make('salary_date')
                ->label('Дата зарплаты')
                ->displayFormat('d.m.Y')
                ->default(fn (): ?string => $this->getOwnerRecord() instanceof PayrollPeriod
                    ? $this->getOwnerRecord()->defaultSalaryDate()->toDateString()
                    : null),
            TextInput::make('salary_amount')
                ->label('Сумма зарплаты')
                ->numeric()
                ->minValue(0)
                ->suffix(' р'),
            Textarea::make('comment')
                ->label('Комментарий')
                ->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('master.name')
                    ->label('Мастер')
                    ->weight('bold'),
                TextColumn::make('earned')
                    ->label('Заработано')
                    ->state(fn (MasterPayout $record): float => $record->earned())
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('accrued')
                    ->label('Начислено')
                    ->state(fn (MasterPayout $record): float => $record->accrued())
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('advance_amount')
                    ->label('Аванс')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->description(fn (MasterPayout $record): ?string => $record->advance_date?->format('d.m.Y'))
                    ->placeholder('—'),
                TextColumn::make('salary_amount')
                    ->label('Зарплата')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->description(fn (MasterPayout $record): ?string => $record->salary_date?->format('d.m.Y'))
                    ->placeholder('—'),
                TextColumn::make('debt')
                    ->label('Долг')
                    ->state(fn (MasterPayout $record): float => $record->debt())
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->color(fn (MasterPayout $record): string => $record->debt() > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                TextColumn::make('comment')
                    ->label('Комментарий')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить мастера')
                    ->visible(fn (): bool => $this->isAdmin()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->isAdmin()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->isAdmin()),
            ]);
    }
}
