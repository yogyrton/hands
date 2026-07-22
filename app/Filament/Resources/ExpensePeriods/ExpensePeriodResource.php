<?php

namespace App\Filament\Resources\ExpensePeriods;

use App\Filament\Resources\ExpensePeriods\Pages\CreateExpensePeriod;
use App\Filament\Resources\ExpensePeriods\Pages\ListExpensePeriods;
use App\Filament\Resources\ExpensePeriods\Pages\ViewExpensePeriod;
use App\Filament\Resources\ExpensePeriods\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\ExpensePeriods\Schemas\ExpensePeriodForm;
use App\Filament\Resources\ExpensePeriods\Schemas\ExpensePeriodInfolist;
use App\Filament\Resources\ExpensePeriods\Tables\ExpensePeriodsTable;
use App\Models\ExpensePeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExpensePeriodResource extends Resource
{
    protected static ?string $model = ExpensePeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return 'Учёт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Расходы';
    }

    public static function getModelLabel(): string
    {
        return 'месяц расходов';
    }

    public static function getPluralModelLabel(): string
    {
        return 'расходы';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return ExpensePeriodForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ExpensePeriodInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensePeriodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpensePeriods::route('/'),
            'create' => CreateExpensePeriod::route('/create'),
            'view' => ViewExpensePeriod::route('/{record}'),
        ];
    }
}
