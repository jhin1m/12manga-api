# Phase 05: Update References and Test

## Context Links
- [Main Plan](./plan.md)
- [Phase 04](./phase-04-create-actions.md)

## Overview

| Field | Value |
|-------|-------|
| Priority | P1 |
| Status | Pending |
| Effort | 45m |

Update all external references to moved models and verify everything works.

## Key Insights

**Why this is critical:**
- Models moved = broken imports everywhere
- Must update systematically
- Test after each file to catch issues early

**Files that import models:**
1. Controllers (AuthController)
2. Seeders (GenreSeeder, MangaSeeder)
3. Factories (UserFactory)
4. Tests (AuthTest)
5. Config files (auth.php - User model path)

## Requirements

### Functional
- Update all import statements
- Update auth config provider
- Update factory namespace
- Verify all tests pass

### Non-Functional
- Zero breaking changes
- All artisan commands work

## Architecture

### Import Mapping

| Old Import | New Import |
|------------|------------|
| `App\Models\User` | `App\Domain\User\Models\User` |
| `App\Models\MangaSeries` | `App\Domain\Manga\Models\MangaSeries` |
| `App\Models\Chapter` | `App\Domain\Manga\Models\Chapter` |
| `App\Models\ChapterImage` | `App\Domain\Manga\Models\ChapterImage` |
| `App\Models\Genre` | `App\Domain\Manga\Models\Genre` |
| `App\Models\Author` | `App\Domain\Manga\Models\Author` |
| `App\Traits\ApiResponse` | `App\Shared\Traits\ApiResponse` |

## Related Code Files

**Update:**
- `app/Http/Controllers/Api/V1/AuthController.php`
- `app/Http/Controllers/Api/ApiController.php`
- `config/auth.php` (User model provider)
- `database/factories/UserFactory.php`
- `database/seeders/GenreSeeder.php`
- `database/seeders/MangaSeeder.php`
- `tests/Feature/Api/V1/AuthTest.php`

## Implementation Steps

### Step 1: Update config/auth.php

The User model path in auth config:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        // OLD:
        // 'model' => App\Models\User::class,
        // NEW:
        'model' => App\Domain\User\Models\User::class,
    ],
],
```

### Step 2: Update AuthController

```php
// app/Http/Controllers/Api/V1/AuthController.php

// OLD:
use App\Models\User;

// NEW:
use App\Domain\User\Models\User;
```

### Step 3: Update ApiController (if uses ApiResponse)

```php
// app/Http/Controllers/Api/ApiController.php

// OLD:
use App\Traits\ApiResponse;

// NEW:
use App\Shared\Traits\ApiResponse;
```

### Step 4: Update UserFactory

```php
// database/factories/UserFactory.php

// OLD:
namespace Database\Factories;
use App\Models\User;

// NEW:
namespace Database\Factories;
use App\Domain\User\Models\User;

// Also update the model property if needed:
protected $model = User::class;
```

**Important:** Laravel expects factories in `Database\Factories` namespace. The factory itself stays there, only the import changes.

### Step 5: Update GenreSeeder

```php
// database/seeders/GenreSeeder.php

// OLD:
use App\Models\Genre;

// NEW:
use App\Domain\Manga\Models\Genre;
```

### Step 6: Update MangaSeeder

```php
// database/seeders/MangaSeeder.php

// OLD:
use App\Models\Author;
use App\Models\Chapter;
use App\Models\ChapterImage;
use App\Models\Genre;
use App\Models\MangaSeries;
use App\Models\User;

// NEW:
use App\Domain\Manga\Models\Author;
use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\ChapterImage;
use App\Domain\Manga\Models\Genre;
use App\Domain\Manga\Models\MangaSeries;
use App\Domain\User\Models\User;
```

### Step 7: Update AuthTest

```php
// tests/Feature/Api/V1/AuthTest.php

// OLD:
use App\Models\User;

// NEW:
use App\Domain\User\Models\User;
```

### Step 8: Run Composer Dump-Autoload

```bash
composer dump-autoload
```

### Step 9: Clear Laravel Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Step 10: Verify with Artisan

```bash
# Should not error
php artisan about

# Try tinker
php artisan tinker
>>> \App\Domain\User\Models\User::count()
>>> \App\Domain\Manga\Models\MangaSeries::count()
```

### Step 11: Run Tests

```bash
./vendor/bin/pest

# Or specific test
./vendor/bin/pest tests/Feature/Api/V1/AuthTest.php
```

### Step 12: Clean Up Old Directories

Only after all tests pass:

```bash
# Check if Models directory is empty
ls -la app/Models/

# If empty, remove
rmdir app/Models

# Check Traits
ls -la app/Traits/

# If empty, remove
rmdir app/Traits
```

## Todo List

- [ ] Update config/auth.php
- [ ] Update AuthController.php
- [ ] Update ApiController.php
- [ ] Update UserFactory.php
- [ ] Update GenreSeeder.php
- [ ] Update MangaSeeder.php
- [ ] Update AuthTest.php
- [ ] Run `composer dump-autoload`
- [ ] Clear Laravel caches
- [ ] Verify with `php artisan about`
- [ ] Test model loading in tinker
- [ ] Run full test suite
- [ ] Clean up old directories
- [ ] Run pint for code style

## Success Criteria

- [ ] All tests pass: `./vendor/bin/pest`
- [ ] `php artisan` commands work
- [ ] Models load correctly in tinker
- [ ] No orphan files in old locations
- [ ] Code style passes: `./vendor/bin/pint --test`

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Missed import | High | Use Grep to find all references |
| Auth broken | Critical | Test login/register first |
| Factory not found | Medium | Check namespace carefully |

## Security Considerations

- Verify auth still works after User model move
- Test protected routes

## Final Verification Commands

```bash
# Full verification sequence
composer dump-autoload
php artisan config:clear
php artisan optimize:clear
php artisan about
./vendor/bin/pest
./vendor/bin/pint --test

# Manual test
curl -X POST http://localhost:8080/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password","password_confirmation":"password"}'
```

## Next Steps

After all phases complete:
1. Commit changes: `git commit -m "refactor: restructure to DDD Lite architecture"`
2. Update README.md if architecture section needs changes
3. Consider creating documentation in `docs/architecture.md`
