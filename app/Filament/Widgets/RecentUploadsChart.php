<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Recent uploads chart widget.
 *
 * Shows chapter upload trends for the last 7 days.
 */
class RecentUploadsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Uploads (Last 7 Days)';
    }

    protected function getData(): array
    {
        $data = collect(range(6, 0))
            ->map(function ($daysAgo) {
                $date = Carbon::now()->subDays($daysAgo);

                return [
                    'label' => $date->format('M j'),
                    'count' => Chapter::whereDate('created_at', $date)->count(),
                ];
            });

        return [
            'datasets' => [
                [
                    'label' => 'Chapters Uploaded',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
