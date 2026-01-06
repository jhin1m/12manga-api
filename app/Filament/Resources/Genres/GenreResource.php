<?php

declare(strict_types=1);

namespace App\Filament\Resources\Genres;

use App\Domain\Manga\Models\Genre;
use App\Filament\Resources\Genres\Pages\CreateGenre;
use App\Filament\Resources\Genres\Pages\EditGenre;
use App\Filament\Resources\Genres\Pages\ListGenres;
use App\Filament\Resources\Genres\Schemas\GenreForm;
use App\Filament\Resources\Genres\Tables\GenresTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Filament resource for Genre model.
 *
 * Manages CRUD operations for manga genres/categories.
 */
class GenreResource extends Resource
{
    protected static ?string $model = Genre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return GenreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GenresTable::configure($table);
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
            'index' => ListGenres::route('/'),
            'create' => CreateGenre::route('/create'),
            'edit' => EditGenre::route('/{record}/edit'),
        ];
    }
}
