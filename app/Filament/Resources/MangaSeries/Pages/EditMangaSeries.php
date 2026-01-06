<?php

declare(strict_types=1);

namespace App\Filament\Resources\MangaSeries\Pages;

use App\Filament\Resources\MangaSeries\MangaSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMangaSeries extends EditRecord
{
    protected static string $resource = MangaSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
