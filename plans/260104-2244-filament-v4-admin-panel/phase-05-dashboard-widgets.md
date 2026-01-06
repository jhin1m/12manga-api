# Phase 05: Dashboard Widgets

## Context Links
- Parent: [plan.md](./plan.md)
- Depends on: [Phase 03](./phase-03-manga-resources.md), [Phase 04](./phase-04-user-management.md)

## Overview
- **Priority**: P2
- **Effort**: 1h
- **Status**: Pending
- **Description**: Create dashboard widgets showing key statistics and recent activity

## Key Insights
- Filament widgets support stats, charts, and tables
- StatsOverviewWidget for numeric metrics
- ChartWidget for trends over time
- TableWidget for recent items lists

## Requirements

### Functional
- Total counts: manga, chapters, users, pending chapters
- Recent uploads widget (last 10 chapters)
- Optional: Charts for upload trends

### Non-Functional
- Fast loading (use count queries, not loading all records)
- Cache expensive queries if needed

## Architecture

```
app/Filament/Widgets/
├── StatsOverview.php           # Counts: manga, chapters, users
├── PendingChaptersWidget.php   # Table of pending approvals
└── RecentUploadsWidget.php     # Chart or list of recent chapters
```

## Related Code Files

| Action | File | Description |
|--------|------|-------------|
| CREATE | `app/Filament/Widgets/StatsOverview.php` | Main stats widget |
| CREATE | `app/Filament/Widgets/PendingChaptersWidget.php` | Pending approval list |
| CREATE | `app/Filament/Widgets/RecentUploadsWidget.php` | Recent activity |

## Implementation Steps

### Step 1: Create StatsOverview Widget
```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

Edit `app/Filament/Widgets/StatsOverview.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

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
                ->color('warning')
                ->url(route('filament.admin.resources.chapters.index', [
                    'tableFilters[is_approved][value]' => '0',
                ])),

            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
```

### Step 2: Create PendingChaptersWidget
```bash
php artisan make:filament-widget PendingChaptersWidget --table
```

Edit `app/Filament/Widgets/PendingChaptersWidget.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingChaptersWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Pending Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Chapter::query()
                    ->pending()
                    ->with(['mangaSeries', 'uploader'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('mangaSeries.title')
                    ->label('Manga')
                    ->limit(30),
                Tables\Columns\TextColumn::make('number')
                    ->label('Chapter'),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded by'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Chapter $record) =>
                        $record->update(['is_approved' => true])
                    ),
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Chapter $record) =>
                        route('filament.admin.resources.chapters.edit', $record)
                    ),
            ])
            ->paginated(false)
            ->emptyStateHeading('No pending chapters')
            ->emptyStateDescription('All chapters have been reviewed')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
```

### Step 3: Create RecentUploadsWidget (Chart)
```bash
php artisan make:filament-widget RecentUploadsChart --chart
```

Edit `app/Filament/Widgets/RecentUploadsChart.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RecentUploadsChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Uploads (Last 7 Days)';
    protected int | string | array $columnSpan = 'full';

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
```

### Step 4: Register Widgets in Panel (if not auto-discovered)
Widgets in `app/Filament/Widgets/` are auto-discovered by default via `discoverWidgets()` in AdminPanelProvider. Verify this is configured:

```php
// In AdminPanelProvider.php panel() method
->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
```

### Step 5: Generate Widget Permissions
```bash
php artisan shield:generate --all
```

## Todo List
- [ ] Create StatsOverview widget with counts
- [ ] Create PendingChaptersWidget with approval actions
- [ ] Create RecentUploadsChart for upload trends
- [ ] Verify widgets appear on dashboard
- [ ] Generate Shield permissions for widgets
- [ ] Test widget interactivity (approve action, links)

## Success Criteria
- [ ] Dashboard shows 4 stat cards (manga, chapters, pending, users)
- [ ] Pending chapters table shows quick approve action
- [ ] Chart displays last 7 days of uploads
- [ ] Stats link to filtered resource lists
- [ ] Empty states display correctly

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Slow queries | Medium | Use count() not load all records |
| Chart performance | Low | Limited to 7 days of data |
| Permission issues | Low | Shield generates widget permissions |

## Security Considerations
- Widget visibility controlled by Shield permissions
- Approve action respects user permissions
- No sensitive data exposed in widgets

## Next Steps
After completing all phases:
1. Run `./vendor/bin/pint` for code style
2. Run `./vendor/bin/pest` for tests
3. Test full admin workflow end-to-end
4. Consider adding custom theme colors
