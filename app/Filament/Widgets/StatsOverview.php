<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard stats widget.
 *
 * Displays key metrics: manga count, chapters, pending approvals, users.
 */
class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Manga', MangaSeries::count())
                ->description('Published series')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            Stat::make('Total Chapters', Chapter::approved()->count())
                ->description('Approved chapters')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Pending Chapters', Chapter::pending()->count())
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
