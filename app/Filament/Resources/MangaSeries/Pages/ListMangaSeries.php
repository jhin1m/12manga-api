<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries\Pages;

use App\Filament\Resources\MangaSeries\MangaSeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMangaSeries extends ListRecords
{
    protected static string $resource = MangaSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
