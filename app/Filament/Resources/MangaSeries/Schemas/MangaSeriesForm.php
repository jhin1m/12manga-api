<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Form schema for MangaSeries resource.
 *
 * Complex form with sections for basic info, media, taxonomy, and stats.
 */
class MangaSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Info')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        KeyValue::make('alt_titles')
                            ->label('Alternative Titles')
                            ->keyLabel('Language')
                            ->valueLabel('Title')
                            ->addActionLabel('Add Title')
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->columnSpanFull(),
                        Select::make('status')
                            ->options([
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'hiatus' => 'Hiatus',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('ongoing'),
                    ]),
                Section::make('Media')
                    ->schema([
                        FileUpload::make('cover_image')
                            ->image()
                            ->directory('manga/covers')
                            ->maxSize(2048)
                            ->imageEditor(),
                    ]),
                Section::make('Taxonomy')
                    ->schema([
                        Select::make('genres')
                            ->relationship('genres', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                            ]),
                        Select::make('authors')
                            ->relationship('authors', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                            ]),
                    ])->columns(2),
                Section::make('Stats')
                    ->schema([
                        TextInput::make('views_count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('average_rating')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
