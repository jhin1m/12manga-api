# Phase 03: Manga Domain Resources

## Context Links
- Parent: [plan.md](./plan.md)
- Depends on: [Phase 02](./phase-02-shield-authentication.md)
- Models: `app/Domain/Manga/Models/`

## Overview
- **Priority**: P1
- **Effort**: 2h
- **Status**: Pending
- **Description**: Create Filament resources for MangaSeries, Chapter, Genre, and Author models

## Key Insights
- Models already exist with proper relationships
- MangaSeries has many-to-many with Authors and Genres
- Chapter belongs to MangaSeries, has images
- All models use Spatie Sluggable for SEO slugs

## Requirements

### Functional
- CRUD for all Manga domain models
- Relationship management (attach genres/authors to manga)
- Chapter approval workflow
- Image upload for covers and chapter pages

### Non-Functional
- Use existing storage structure
- Follow project naming conventions
- Type hints on all methods

## Architecture

```
app/Filament/Resources/
├── MangaSeriesResource.php
├── MangaSeriesResource/
│   └── Pages/
│       ├── ListMangaSeries.php
│       ├── CreateMangaSeries.php
│       └── EditMangaSeries.php
├── ChapterResource.php
├── ChapterResource/
│   └── Pages/
│       ├── ListChapters.php
│       ├── CreateChapter.php
│       └── EditChapter.php
├── GenreResource.php
├── GenreResource/
│   └── Pages/
│       └── ...
├── AuthorResource.php
└── AuthorResource/
    └── Pages/
        └── ...
```

## Related Code Files

| Action | File | Description |
|--------|------|-------------|
| CREATE | `app/Filament/Resources/MangaSeriesResource.php` | Main manga resource |
| CREATE | `app/Filament/Resources/ChapterResource.php` | Chapter management |
| CREATE | `app/Filament/Resources/GenreResource.php` | Genre CRUD |
| CREATE | `app/Filament/Resources/AuthorResource.php` | Author CRUD |
| CREATE | `app/Filament/Resources/*/Pages/*.php` | Resource pages |

## Implementation Steps

### Step 1: Generate GenreResource (Simplest First)
```bash
php artisan make:filament-resource Genre --generate
```

Edit `app/Filament/Resources/GenreResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Manga\Models\Genre;
use App\Filament\Resources\GenreResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GenreResource extends Resource
{
    protected static ?string $model = Genre::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Manga';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('slug')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Auto-generated from name'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manga_series_count')
                    ->counts('mangaSeries')
                    ->label('Manga Count'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGenres::route('/'),
            'create' => Pages\CreateGenre::route('/create'),
            'edit' => Pages\EditGenre::route('/{record}/edit'),
        ];
    }
}
```

### Step 2: Generate AuthorResource
```bash
php artisan make:filament-resource Author --generate
```

Edit `app/Filament/Resources/AuthorResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Manga\Models\Author;
use App\Filament\Resources\AuthorResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Manga';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('slug')
                ->disabled()
                ->dehydrated(false)
                ->helperText('Auto-generated from name'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('manga_series_count')
                    ->counts('mangaSeries')
                    ->label('Works'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
```

### Step 3: Generate MangaSeriesResource (Complex)
```bash
php artisan make:filament-resource MangaSeries --generate
```

Edit `app/Filament/Resources/MangaSeriesResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Genre;
use App\Domain\Manga\Models\MangaSeries;
use App\Filament\Resources\MangaSeriesResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MangaSeriesResource extends Resource
{
    protected static ?string $model = MangaSeries::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Manga';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\KeyValue::make('alt_titles')
                        ->label('Alternative Titles')
                        ->keyLabel('Language')
                        ->valueLabel('Title')
                        ->addActionLabel('Add Title')
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('description')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'ongoing' => 'Ongoing',
                            'completed' => 'Completed',
                            'hiatus' => 'Hiatus',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->default('ongoing'),
                ]),
            Forms\Components\Section::make('Media')
                ->schema([
                    Forms\Components\FileUpload::make('cover_image')
                        ->image()
                        ->directory('manga/covers')
                        ->maxSize(2048)
                        ->imageEditor(),
                ]),
            Forms\Components\Section::make('Taxonomy')
                ->schema([
                    Forms\Components\Select::make('genres')
                        ->relationship('genres', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                        ]),
                    Forms\Components\Select::make('authors')
                        ->relationship('authors', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                        ]),
                ])->columns(2),
            Forms\Components\Section::make('Stats')
                ->schema([
                    Forms\Components\TextInput::make('views_count')
                        ->numeric()
                        ->default(0)
                        ->disabled(),
                    Forms\Components\TextInput::make('average_rating')
                        ->numeric()
                        ->default(0)
                        ->disabled(),
                ])->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ongoing' => 'success',
                        'completed' => 'info',
                        'hiatus' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('chapters_count')
                    ->counts('chapters')
                    ->label('Chapters'),
                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()
                    ->sortable()
                    ->label('Views'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'hiatus' => 'Hiatus',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('genres')
                    ->relationship('genres', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMangaSeries::route('/'),
            'create' => Pages\CreateMangaSeries::route('/create'),
            'view' => Pages\ViewMangaSeries::route('/{record}'),
            'edit' => Pages\EditMangaSeries::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }
}
```

### Step 4: Generate ChapterResource
```bash
php artisan make:filament-resource Chapter --generate
```

Edit `app/Filament/Resources/ChapterResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use App\Filament\Resources\ChapterResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChapterResource extends Resource
{
    protected static ?string $model = Chapter::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manga';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Chapter Info')
                ->schema([
                    Forms\Components\Select::make('manga_series_id')
                        ->label('Manga')
                        ->relationship('mangaSeries', 'title')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('number')
                        ->label('Chapter Number')
                        ->required()
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0),
                    Forms\Components\TextInput::make('title')
                        ->maxLength(255)
                        ->placeholder('Optional chapter title'),
                    Forms\Components\Toggle::make('is_approved')
                        ->label('Approved')
                        ->default(false)
                        ->helperText('Only approved chapters are visible to readers'),
                ]),
            Forms\Components\Section::make('Pages')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('Chapter Pages')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->directory('chapters')
                        ->maxFiles(100)
                        ->helperText('Upload chapter images in reading order'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mangaSeries.title')
                    ->label('Manga')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('number')
                    ->label('Ch.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploader')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('images_count')
                    ->counts('images')
                    ->label('Pages'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('manga_series_id')
                    ->label('Manga')
                    ->relationship('mangaSeries', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All')
                    ->trueLabel('Approved')
                    ->falseLabel('Pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Chapter $record): bool => ! $record->is_approved)
                    ->action(fn (Chapter $record) => $record->update(['is_approved' => true])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChapters::route('/'),
            'create' => Pages\CreateChapter::route('/create'),
            'edit' => Pages\EditChapter::route('/{record}/edit'),
        ];
    }
}
```

### Step 5: Generate Permissions
```bash
php artisan shield:generate --all
```

## Todo List
- [ ] Generate GenreResource with `make:filament-resource`
- [ ] Configure GenreResource form and table
- [ ] Generate AuthorResource with `make:filament-resource`
- [ ] Configure AuthorResource form and table
- [ ] Generate MangaSeriesResource with `make:filament-resource`
- [ ] Configure MangaSeriesResource with relationships
- [ ] Generate ChapterResource with `make:filament-resource`
- [ ] Configure ChapterResource with approval workflow
- [ ] Add ViewMangaSeries page for detail view
- [ ] Run `shield:generate --all` for permissions
- [ ] Test all CRUD operations

## Success Criteria
- [ ] All resources visible in admin sidebar under "Manga" group
- [ ] Create/Edit/Delete works for all models
- [ ] Relationships (genres, authors) work in MangaSeries form
- [ ] Chapter approval action works
- [ ] File uploads work for covers and chapter images

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| File upload storage config | Medium | Use existing storage disk |
| Many-to-many sync issues | Low | Filament handles via relationship |
| Image ordering in chapters | Medium | Use reorderable file upload |

## Security Considerations
- File uploads restricted to images only
- Max file size limits enforced
- Shield permissions control access per resource

## Next Steps
→ Phase 04: User Management Resource
