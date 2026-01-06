<?php

declare(strict_types=1);

namespace App\Filament\Resources\Genres\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Form schema for Genre resource.
 *
 * Why slug is disabled?
 * - Spatie Sluggable automatically generates slugs from name field
 * - Prevents manual slug editing to maintain consistency
 */
class GenreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Auto-generated from name'),
            ]);
    }
}
