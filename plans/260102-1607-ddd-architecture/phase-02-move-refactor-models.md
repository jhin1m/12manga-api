# Phase 02: Move and Refactor Models

## Context Links
- [Main Plan](./plan.md)
- [Phase 01](./phase-01-create-folder-structure.md)

## Overview

| Field | Value |
|-------|-------|
| Priority | P1 |
| Status | Pending |
| Effort | 45m |

Move all models from `app/Models/` to appropriate domain folders and update namespaces.

## Key Insights

**Domain assignment:**
- **Manga Domain:** MangaSeries, Chapter, ChapterImage, Genre, Author
  - Why? These are core manga business entities
- **User Domain:** User
  - Why? User is central to user-related features (profile, auth)
- **Community Domain:** (empty for now)
  - Future: Comment, Rating models

**Why move ApiResponse trait?**
- It's shared across domains
- Should live in `Shared/Traits/`

## Requirements

### Functional
- Move 6 models to correct domains
- Update all namespaces
- Move ApiResponse trait to Shared
- Update all imports

### Non-Functional
- Maintain backward compatibility via composer autoload
- Keep PHPDoc accurate

## Architecture

### Model Distribution

| Model | From | To | Namespace |
|-------|------|-----|-----------|
| MangaSeries | app/Models/ | app/Domain/Manga/Models/ | App\Domain\Manga\Models |
| Chapter | app/Models/ | app/Domain/Manga/Models/ | App\Domain\Manga\Models |
| ChapterImage | app/Models/ | app/Domain/Manga/Models/ | App\Domain\Manga\Models |
| Genre | app/Models/ | app/Domain/Manga/Models/ | App\Domain\Manga\Models |
| Author | app/Models/ | app/Domain/Manga/Models/ | App\Domain\Manga\Models |
| User | app/Models/ | app/Domain/User/Models/ | App\Domain\User\Models |

### Trait Distribution

| Trait | From | To | Namespace |
|-------|------|-----|-----------|
| ApiResponse | app/Traits/ | app/Shared/Traits/ | App\Shared\Traits |

## Related Code Files

**Move (with namespace update):**
- `app/Models/MangaSeries.php` → `app/Domain/Manga/Models/MangaSeries.php`
- `app/Models/Chapter.php` → `app/Domain/Manga/Models/Chapter.php`
- `app/Models/ChapterImage.php` → `app/Domain/Manga/Models/ChapterImage.php`
- `app/Models/Genre.php` → `app/Domain/Manga/Models/Genre.php`
- `app/Models/Author.php` → `app/Domain/Manga/Models/Author.php`
- `app/Models/User.php` → `app/Domain/User/Models/User.php`
- `app/Traits/ApiResponse.php` → `app/Shared/Traits/ApiResponse.php`

**Delete (after moving):**
- `app/Models/` directory (if empty)
- `app/Traits/` directory (if empty)

## Implementation Steps

### Step 1: Move Manga Domain Models

For each model (MangaSeries, Chapter, ChapterImage, Genre, Author):

```bash
# Move file
mv app/Models/MangaSeries.php app/Domain/Manga/Models/MangaSeries.php
```

Update namespace in file:
```php
// OLD
namespace App\Models;

// NEW
namespace App\Domain\Manga\Models;
```

Update imports within the model:
```php
// In MangaSeries.php - update references to other models
use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Genre;
use App\Domain\Manga\Models\Chapter;
use App\Domain\User\Models\User;  // For followers relation
```

### Step 2: Move User Domain Model

```bash
mv app/Models/User.php app/Domain/User/Models/User.php
```

Update namespace:
```php
namespace App\Domain\User\Models;
```

Update imports:
```php
use App\Domain\Manga\Models\Chapter;      // uploadedChapters
use App\Domain\Manga\Models\MangaSeries;  // followedManga
```

### Step 3: Move Shared Trait

```bash
mv app/Traits/ApiResponse.php app/Shared/Traits/ApiResponse.php
```

Update namespace:
```php
namespace App\Shared\Traits;
```

### Step 4: Update Cross-Model References

**MangaSeries.php** - update relations:
```php
use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\Genre;
use App\Domain\User\Models\User;
```

**Chapter.php** - update relations:
```php
use App\Domain\Manga\Models\ChapterImage;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
```

**ChapterImage.php** - update relations:
```php
use App\Domain\Manga\Models\Chapter;
```

**Author.php** - update relations:
```php
use App\Domain\Manga\Models\MangaSeries;
```

**Genre.php** - update relations:
```php
use App\Domain\Manga\Models\MangaSeries;
```

**User.php** - update relations:
```php
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
```

### Step 5: Clean up old directories

```bash
rmdir app/Models  # Only if empty
rmdir app/Traits  # Only if empty
```

## Todo List

- [ ] Move MangaSeries.php, update namespace
- [ ] Move Chapter.php, update namespace
- [ ] Move ChapterImage.php, update namespace
- [ ] Move Genre.php, update namespace
- [ ] Move Author.php, update namespace
- [ ] Move User.php, update namespace
- [ ] Move ApiResponse.php, update namespace
- [ ] Update all cross-model imports
- [ ] Run `composer dump-autoload`
- [ ] Verify with `php artisan tinker` (try loading a model)
- [ ] Delete old directories

## Success Criteria

- [ ] All models in correct domain folders
- [ ] All namespaces correct
- [ ] No PHP errors on `php artisan`
- [ ] Models load correctly in tinker

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Broken imports | High | Update systematically, test after each |
| Autoloader cache | Medium | Run composer dump-autoload |
| IDE confusion | Low | Restart IDE after changes |

## Security Considerations

N/A - structural change only

## Next Steps

Proceed to [Phase 03: Create Services](./phase-03-create-services.md)
