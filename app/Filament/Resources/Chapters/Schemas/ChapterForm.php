<?php

declare(strict_types=1);

namespace App\Filament\Resources\Chapters\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Form schema for Chapter resource.
 *
 * Includes manga selection, chapter info, approval toggle, and image upload.
 */
class ChapterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chapter Info')
                    ->schema([
                        Select::make('manga_series_id')
                            ->label('Manga')
                            ->relationship('mangaSeries', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('number')
                            ->label('Chapter Number')
                            ->required()
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0),
                        TextInput::make('title')
                            ->maxLength(255)
                            ->placeholder('Optional chapter title'),
                        Toggle::make('is_approved')
                            ->label('Approved')
                            ->default(false)
                            ->helperText('Only approved chapters are visible to readers'),
                    ]),
                Section::make('Pages')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Chapter Pages')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->directory('chapters')
                            ->maxFiles(100)
                            ->helperText('Upload chapter images in reading order'),
                    ]),
            ]);
    }
}
