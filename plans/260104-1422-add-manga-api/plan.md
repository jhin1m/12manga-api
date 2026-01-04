---
title: "Add Manga API Endpoints"
description: "Implement CRUD APIs for Manga, Chapters, Genres, Authors"
status: completed
priority: P1
effort: 8h
branch: main
tags: [api, manga, crud, laravel]
created: 2026-01-04
reviewed: 2026-01-04
review_report: /Users/jhin1m/Desktop/ducanh-project/laravel-api-kit/plans/reports/code-reviewer-260104-1458-manga-api-review.md
completed_at: 2026-01-04 15:15
---

# Add Manga API Endpoints

## Overview

This plan implements RESTful API endpoints for the Manga domain. The codebase already has Models, Services, and Actions in place - we need to create Controllers, Resources, Requests, and Routes to expose them via API.

## Current State

| Component | Status |
|-----------|--------|
| Models | Done (MangaSeries, Chapter, ChapterImage, Genre, Author) |
| Services | Done (MangaService, ChapterService) |
| Actions | Done (CreateMangaAction, UpdateMangaAction, ApproveChapterAction) |
| Controllers | Done (Manga, Chapter, Genre, Author) |
| Resources | Done (Manga, Chapter, Genre, Author) |
| Form Requests | Done (Store/Update Manga, Store/Update Chapter) |
| Routes | Done (v1 manga series) |
| Tests | Done (62 tests, 257 assertions) |

## Implementation Phases

| Phase | Description | Status | Effort |
|-------|-------------|--------|--------|
| [Phase 01](./phase-01-manga-crud-api.md) | MangaSeries CRUD API | Completed | 2.5h |
| [Phase 02](./phase-02-chapter-api.md) | Chapter API (nested under manga) | Completed | 2.5h |
| [Phase 03](./phase-03-genre-author-api.md) | Genre & Author APIs (supporting resources) | Completed | 2h |
| [Security Fixes] | Role-based Auth, SQLi fixes, Sanitization | Completed | 1h |

## API Endpoints Summary

### Phase 01 - Manga Series
```
GET    /api/v1/manga              # List manga (paginated, filterable)
GET    /api/v1/manga/{slug}       # Show single manga
POST   /api/v1/manga              # Create manga (Admin)
PUT    /api/v1/manga/{slug}       # Update manga (Admin)
DELETE /api/v1/manga/{slug}       # Soft delete manga (Admin)
GET    /api/v1/manga/popular      # Popular manga list
GET    /api/v1/manga/latest       # Latest updated manga
GET    /api/v1/manga/search       # Search manga by keyword
```

### Phase 02 - Chapters
```
GET    /api/v1/manga/{slug}/chapters           # List chapters
GET    /api/v1/manga/{slug}/chapters/{number}  # Show chapter with images
POST   /api/v1/manga/{slug}/chapters           # Create chapter (Admin)
PUT    /api/v1/manga/{slug}/chapters/{number}  # Update chapter (Admin)
DELETE /api/v1/manga/{slug}/chapters/{number}  # Delete chapter (Admin)
POST   /api/v1/chapters/{id}/approve           # Approve chapter (Admin)
```

### Phase 03 - Genres & Authors
```
GET    /api/v1/genres             # List all genres
GET    /api/v1/genres/{slug}      # Show genre with manga
GET    /api/v1/authors            # List all authors
GET    /api/v1/authors/{slug}     # Show author with manga
```

## Architecture Decisions

1. **Slug-based routing** - User-friendly URLs (`/manga/one-piece` vs `/manga/123`)
2. **Nested resources** - Chapters under manga (`/manga/{slug}/chapters`)
3. **Spatie QueryBuilder** - Flexible filtering/sorting on list endpoints
4. **Admin-only mutations** - Create/Update/Delete require Admin role

## Files to Create

```
app/Http/Controllers/Api/V1/
├── MangaController.php
├── ChapterController.php
├── GenreController.php
└── AuthorController.php

app/Http/Requests/Api/V1/
├── StoreMangaRequest.php
├── UpdateMangaRequest.php
├── StoreChapterRequest.php
└── UpdateChapterRequest.php

app/Http/Resources/
├── MangaResource.php
├── MangaCollection.php
├── ChapterResource.php
├── ChapterImageResource.php
├── GenreResource.php
└── AuthorResource.php

tests/Feature/Api/V1/
├── MangaTest.php
├── ChapterTest.php
├── GenreTest.php
└── AuthorTest.php
```

## Success Criteria

- [x] All endpoints return standardized JSON via ApiResponse trait
- [x] Admin-only routes protected with `auth:sanctum` + `role:admin` middleware
- [x] Tests cover happy path + auth + validation errors
- [x] Scramble docs auto-generate correctly
- [x] Pint passes with no style issues

## Review Findings (2026-01-04)

**Status:** ✅ Implementation complete, ✅ Security issues resolved

**Resolved Issues:**
1. **FIXED**: Missing `role:admin` middleware on all mutation routes
2. **FIXED**: SQL injection risk in MangaSeries search scope (parameterized correctly)
3. **FIXED**: Missing input sanitization for cover_image URLs (http/https whitelist)
4. **TODO**: Database unique constraint enforcement for chapter numbers (handled at API layer for now)

**Remaining High Priority:**
5. N+1 queries in Genre/Author show endpoints (Eager loading needed)
6. per_page limits on pagination (resource exhaustion risk)

**Metrics:**
- Tests: 62 passing, 257 assertions ✅
- Code style: Pint passes ✅
- Type coverage: ~98% ✅
- Security score: 9/10 (Critical fixes applied) ✅
- Performance score: 7/10 ⚠️

**Next Actions:**
1. Optimize Genre/Author controllers with eager loading
2. Implement per_page limits in MangaService
3. Finalize Phase 04 (User Personalization)

See full report: `/Users/jhin1m/Desktop/ducanh-project/laravel-api-kit/plans/reports/code-reviewer-260104-1458-manga-api-review.md`

## Dependencies

- Existing Domain layer (Models, Services, Actions)
- Laravel Sanctum (authentication)
- Spatie Permission (role-based access)
- Spatie QueryBuilder (filtering)

## Risks

| Risk | Mitigation |
|------|------------|
| Chapter images storage | Use placeholder paths; actual upload in future phase |
| Complex filters | Start simple, add Spatie QueryBuilder filters iteratively |
