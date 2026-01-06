<?php

declare(strict_types=1);

namespace App\Filament\Resources\Chapters\Tables;

use App\Domain\Manga\Models\Chapter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Table configuration for Chapter resource.
 *
 * Includes approval workflow with quick approve actions.
 */
class ChaptersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mangaSeries.title')
                    ->label('Manga')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('number')
                    ->label('Ch.')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->placeholder('—'),
                TextColumn::make('images_count')
                    ->counts('images')
                    ->label('Pages'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('manga_series_id')
                    ->label('Manga')
                    ->relationship('mangaSeries', 'title')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Chapter $record): bool => ! $record->is_approved)
                    ->action(fn (Chapter $record) => $record->update(['is_approved' => true])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
