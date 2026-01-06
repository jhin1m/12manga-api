<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries;

use App\Domain\Manga\Models\MangaSeries;
use App\Filament\Resources\MangaSeries\Pages\CreateMangaSeries;
use App\Filament\Resources\MangaSeries\Pages\EditMangaSeries;
use App\Filament\Resources\MangaSeries\Pages\ListMangaSeries;
use App\Filament\Resources\MangaSeries\Pages\ViewMangaSeries;
use App\Filament\Resources\MangaSeries\Schemas\MangaSeriesForm;
use App\Filament\Resources\MangaSeries\Tables\MangaSeriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Filament resource for MangaSeries model.
 *
 * Manages CRUD operations for manga series with relationships.
 */
class MangaSeriesResource extends Resource
{
    protected static ?string $model = MangaSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return MangaSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MangaSeriesTable::configure($table);
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
            'index' => ListMangaSeries::route('/'),
            'create' => CreateMangaSeries::route('/create'),
            'view' => ViewMangaSeries::route('/{record}'),
            'edit' => EditMangaSeries::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }
}
