<?php

namespace App\Filament\Resources\Masters;

use App\Filament\Resources\Masters\Pages\CreateMaster;
use App\Filament\Resources\Masters\Pages\EditMaster;
use App\Filament\Resources\Masters\Pages\ListMasters;
use App\Filament\Resources\Masters\Schemas\MasterForm;
use App\Filament\Resources\Masters\Tables\MastersTable;
use App\Models\Master;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MasterResource extends Resource
{
    protected static ?string $model = Master::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Мастера';
    }

    public static function getModelLabel(): string
    {
        return 'мастер';
    }

    public static function getPluralModelLabel(): string
    {
        return 'мастера';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return MasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MastersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasters::route('/'),
            'create' => CreateMaster::route('/create'),
            'edit' => EditMaster::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
