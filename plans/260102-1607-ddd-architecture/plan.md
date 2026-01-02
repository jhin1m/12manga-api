---
title: "DDD Lite Architecture Restructure"
description: "Restructure Laravel project from default to Domain-Driven Design Lite with 3 domains: Manga, User, Community"
status: completed
priority: P1
effort: 3h
branch: main
tags: [architecture, refactor, ddd, backend]
created: 2026-01-02
completed: 2026-01-02
---

# DDD Lite Architecture Restructure

## Overview

Restructure Laravel project from default `app/Models` to DDD Lite architecture. This enables:
- Clear domain boundaries
- Scalable codebase structure
- Team-friendly development
- Microservice-ready architecture

## Current State

```
app/
├── Http/                    # Controllers, Requests, Resources
├── Models/                  # All models in one place (6 files)
├── Traits/                  # ApiResponse trait
└── Providers/
```

**Models to migrate:** User, MangaSeries, Chapter, ChapterImage, Author, Genre

## Target Architecture

```
app/
├── Domain/
│   ├── Manga/
│   │   ├── Models/          # MangaSeries, Chapter, ChapterImage, Genre, Author
│   │   ├── Services/        # MangaService, ChapterService
│   │   └── Actions/         # CreateManga, UpdateManga, etc.
│   │
│   ├── User/
│   │   ├── Models/          # User
│   │   ├── Services/        # UserService, FollowService
│   │   └── Actions/         # UpdateProfile, FollowManga, etc.
│   │
│   └── Community/           # Placeholder for future
│       ├── Models/
│       ├── Services/
│       └── Actions/
│
├── Http/                    # Keep as-is
└── Shared/
    └── Traits/              # ApiResponse (moved from app/Traits)
```

## Phases

| # | Phase | Status | Effort | Link |
|---|-------|--------|--------|------|
| 1 | Create folder structure | ✅ Completed | 15m | [phase-01](./phase-01-create-folder-structure.md) |
| 2 | Move and refactor Models | ✅ Completed | 45m | [phase-02-move-refactor-models.md](./phase-02-move-refactor-models.md) |
| 3 | Create Services layer | ✅ Completed | 45m | [phase-03-create-services.md](./phase-03-create-services.md) |
| 4 | Create Actions layer | ✅ Completed | 30m | [phase-04-create-actions.md](./phase-04-create-actions.md) |
| 5 | Update references and test | ✅ Completed | 45m | [phase-05-update-references-test.md](./phase-05-update-references-test.md) |

## Dependencies

- spatie/laravel-sluggable (already installed)
- spatie/laravel-permission (already installed)
- laravel/sanctum (already installed)

## Files Affected

**Move:**
- 6 Models from `app/Models/` to `app/Domain/*/Models/`
- 1 Trait from `app/Traits/` to `app/Shared/Traits/`

**Update imports in:**
- `app/Http/Controllers/Api/V1/AuthController.php`
- `database/factories/UserFactory.php`
- `database/seeders/GenreSeeder.php`
- `database/seeders/MangaSeeder.php`
- `tests/Feature/Api/V1/AuthTest.php`

## Risk Assessment

| Risk | Mitigation |
|------|------------|
| Broken imports | Run tests after each phase |
| Autoloader issues | Run `composer dump-autoload` |
| IDE not recognizing | Restart IDE, regenerate helper files |

## Success Criteria

- [x] All models in domain folders ✅
- [x] All tests passing (11 tests, 39 assertions) ✅
- [x] No broken imports ✅
- [x] Services structure ready for business logic ✅
- [x] Actions structure ready for single-purpose operations ✅

## Review Report

**Code Review**: [code-reviewer-260102-1633-ddd-architecture-review.md](../reports/code-reviewer-260102-1633-ddd-architecture-review.md)
**Grade**: A- (Excellent with minor improvements)
**Status**: ✅ APPROVED for production with recommended improvements
