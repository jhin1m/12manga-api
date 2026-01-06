<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries\Pages;

use App\Filament\Resources\MangaSeries\MangaSeriesResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMangaSeries extends ViewRecord
{
    protected static string $resource = MangaSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
