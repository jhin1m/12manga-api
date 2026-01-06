<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries\Pages;

use App\Filament\Resources\MangaSeries\MangaSeriesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMangaSeries extends CreateRecord
{
    protected static string $resource = MangaSeriesResource::class;
}
