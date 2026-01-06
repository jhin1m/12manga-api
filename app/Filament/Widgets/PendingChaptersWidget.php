<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Manga\Models\Chapter;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Pending chapters approval widget.
 *
 * Shows recent unapproved chapters with quick approve action.
 */
class PendingChaptersWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Pending Approvals';
    }

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
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Chapter $record) => $record->update(['is_approved' => true])
                    ),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Chapter $record) => route('filament.admin.resources.chapters.edit', $record)
                    ),
            ])
            ->paginated(false)
            ->emptyStateHeading('No pending chapters')
            ->emptyStateDescription('All chapters have been reviewed')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
