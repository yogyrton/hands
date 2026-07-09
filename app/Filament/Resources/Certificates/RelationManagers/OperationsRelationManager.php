<?php

namespace App\Filament\Resources\Certificates\RelationManagers;

use App\Enums\CertificateOperationType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OperationsRelationManager extends RelationManager
{
    protected static string $relationship = 'operations';

    protected static ?string $title = 'История операций';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i'),
                TextColumn::make('type')
                    ->label('Операция')
                    ->badge()
                    ->formatStateUsing(fn (CertificateOperationType $state): string => $state->label()),
                TextColumn::make('amount')
                    ->label('Значение'),
                TextColumn::make('visit.master.name')
                    ->label('Мастер')
                    ->placeholder('—'),
                TextColumn::make('visit.service.name')
                    ->label('Услуга')
                    ->placeholder('—'),
                TextColumn::make('visit.performed_at')
                    ->label('Посещение')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—'),
            ]);
    }
}
