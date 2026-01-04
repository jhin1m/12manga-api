---
parent: ./plan.md
dependencies: [./phase-01-manga-crud-api.md]
docs: [../docs/code-standards.md, ../docs/system-architecture.md]
---

# Phase 02: Chapter API

## Overview

| Field | Value |
|-------|-------|
| Date | 2026-01-04 |
| Priority | P1 |
| Effort | 2.5h |
| Implementation | Completed |
| Review | Completed |
| Completed At | 2026-01-04 14:45 |

## Context

Implement Chapter API endpoints nested under manga. Chapters are always accessed in context of their parent manga series.

## Key Insights

1. **Chapter model** features:
   - Belongs to MangaSeries
   - Has many ChapterImages (pages)
   - Tracks uploader (user who added it)
   - Approval workflow (`is_approved` boolean)
   - Scopes: `approved()`, `pending()`

2. **ChapterService** provides:
   - `getApprovedChapters()` - list for public
   - `getPendingChapters()` - admin moderation queue
   - `approve()` - approve a chapter
   - `findByNumber()` - find by manga + chapter number

3. **ApproveChapterAction** exists for approval workflow

## Requirements

### Functional
- List chapters for a manga (approved only for public)
- Show chapter with images
- Create chapter (Admin)
- Update chapter (Admin)
- Delete chapter (Admin)
- Approve pending chapter (Admin)
- List pending chapters for moderation (Admin)

### Non-Functional
- Images ordered by `order` field
- Response includes image URLs for reader

## Architecture

Nested resource pattern:
```
/manga/{manga:slug}/chapters
/manga/{manga:slug}/chapters/{chapter:number}
```

Why nested?
- Chapters always belong to a manga
- URL clearly shows relationship
- Route model binding scopes chapter to manga

## Related Code Files

| File | Purpose |
|------|---------|
| `app/Domain/Manga/Models/Chapter.php` | Chapter model |
| `app/Domain/Manga/Models/ChapterImage.php` | Page images model |
| `app/Domain/Manga/Services/ChapterService.php` | Chapter operations |
| `app/Domain/Manga/Actions/ApproveChapterAction.php` | Approval action |

## Implementation Steps

### Step 1: Create ChapterResource and ChapterImageResource
Transform chapter data to JSON. Include images when loaded.

```php
// ChapterResource fields:
// - id, number, title, slug, is_approved
// - created_at, uploader (when loaded)
// - images (when loaded, use ChapterImageResource)

// ChapterImageResource fields:
// - id, image_path, order
```

### Step 2: Create Form Requests

**StoreChapterRequest.php**:
```php
// Required: number (decimal), images (array of files/paths)
// Optional: title (string)
// Auto-set: manga_series_id (from route), uploader_id (auth user)
```

**UpdateChapterRequest.php**:
```php
// All optional for PATCH semantics
```

### Step 3: Create ChapterController
Nested controller for chapters.

Methods:
- `index($manga)` - List approved chapters
- `show($manga, $number)` - Single chapter with images
- `store($manga)` - Create chapter (Admin)
- `update($manga, $number)` - Update (Admin)
- `destroy($manga, $number)` - Delete (Admin)
- `pending()` - List pending for moderation (Admin)
- `approve($chapter)` - Approve chapter (Admin)

### Step 4: Define Routes

```php
// Public - nested under manga
Route::get('manga/{manga:slug}/chapters', [ChapterController::class, 'index']);
Route::get('manga/{manga:slug}/chapters/{number}', [ChapterController::class, 'show']);

// Admin - chapter management
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('manga/{manga:slug}/chapters', [ChapterController::class, 'store']);
    Route::put('manga/{manga:slug}/chapters/{number}', [ChapterController::class, 'update']);
    Route::delete('manga/{manga:slug}/chapters/{number}', [ChapterController::class, 'destroy']);

    // Moderation
    Route::get('chapters/pending', [ChapterController::class, 'pending']);
    Route::post('chapters/{chapter}/approve', [ChapterController::class, 'approve']);
});
```

### Step 5: Write Tests

Test cases:
- [x] `it lists approved chapters for a manga`
- [x] `it shows chapter with images`
- [x] `it hides unapproved chapters from public`
- [x] `admin can create chapter`
- [x] `admin can approve pending chapter`
- [x] `it returns 404 for non-existent chapter`

## Todo List

- [x] Create ChapterImageResource.php
- [x] Create ChapterResource.php
- [x] Create StoreChapterRequest.php
- [x] Create UpdateChapterRequest.php
- [x] Create ChapterController.php
- [x] Add routes to api.php
- [x] Write ChapterTest.php
- [x] Run Pint and fix style
- [x] Run tests and verify pass

## Success Criteria

- [x] `GET /api/v1/manga/{slug}/chapters` lists approved chapters
- [x] `GET /api/v1/manga/{slug}/chapters/{number}` shows chapter with images
- [x] Admin routes protected with `role:admin`
- [x] Approval workflow works
- [x] All tests pass

## Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Image upload complexity | High | Medium | Phase 02 uses paths only; file upload in future |
| Chapter number conflicts | Low | Low | Unique constraint on (manga_id, number) |

## Security Considerations

- Only approved chapters visible to public
- Chapter creation requires authentication
- Uploader tracked for moderation

## Next Steps

After Phase 02:
1. Move to Phase 03 (Genre & Author APIs)
2. Consider image upload service in future phase
