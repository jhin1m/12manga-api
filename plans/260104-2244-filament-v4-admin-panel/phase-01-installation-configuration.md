# Phase 01: Installation & Configuration

## Context Links
- Parent: [plan.md](./plan.md)
- Docs: [Filament v4 Installation](https://filamentphp.com/docs/4.x/panels/installation)

## Overview
- **Priority**: P1 (Blocking)
- **Effort**: 1h
- **Status**: Pending
- **Description**: Install Filament v4 and configure admin panel at `/admin` route

## Key Insights
- Filament v4 supports Laravel 12 with PHP 8.3+
- Core package `filament/filament` includes panels, forms, tables, notifications
- Panel provider pattern for configuration
- Uses existing User model as auth provider

## Requirements

### Functional
- Filament admin panel accessible at `/admin`
- Login page for admin authentication
- Basic panel configuration with branding

### Non-Functional
- No conflict with existing API routes
- Minimal bundle size (no unused plugins)

## Architecture

```
app/
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php    # NEW: Panel configuration
├── Filament/
│   └── Resources/                     # Future: resources go here
└── Domain/User/Models/User.php        # Existing: implements FilamentUser
```

## Related Code Files

| Action | File | Description |
|--------|------|-------------|
| CREATE | `app/Providers/Filament/AdminPanelProvider.php` | Panel configuration |
| MODIFY | `app/Domain/User/Models/User.php` | Implement `FilamentUser` contract |
| MODIFY | `config/app.php` | Register panel provider |
| CREATE | `database/migrations/xxx_create_filament_tables.php` | If needed by Filament |

## Implementation Steps

### Step 1: Install Filament v4
```bash
composer require filament/filament:"^4.0"
```

### Step 2: Create Admin Panel
```bash
php artisan filament:install --panels
```
When prompted:
- Panel ID: `admin`
- This creates `AdminPanelProvider.php`

### Step 3: Configure Panel Provider
Edit `app/Providers/Filament/AdminPanelProvider.php`:

```php
<?php

namespace App\Providers\Filament;

use App\Domain\User\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->authGuard('web')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

### Step 4: Implement FilamentUser on User Model
Modify `app/Domain/User/Models/User.php`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ... existing code ...

    /**
     * Determine if user can access Filament admin panel.
     *
     * Why this method?
     * - Restricts panel access to users with 'admin' role
     * - Prevents regular users from accessing admin
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only users with 'admin' role can access
        return $this->hasRole('admin');
    }
}
```

### Step 5: Create Filament Directories
```bash
mkdir -p app/Filament/Resources
mkdir -p app/Filament/Pages
mkdir -p app/Filament/Widgets
```

### Step 6: Run Migrations
```bash
php artisan migrate
```

### Step 7: Verify Installation
```bash
php artisan serve
# Visit http://localhost:8000/admin
```

## Todo List
- [ ] Install Filament v4 via composer
- [ ] Run `filament:install --panels` command
- [ ] Configure AdminPanelProvider with custom settings
- [ ] Implement FilamentUser contract on User model
- [ ] Create required directories
- [ ] Run migrations if needed
- [ ] Test admin login works

## Success Criteria
- [ ] `/admin` shows Filament login page
- [ ] Admin user can login successfully
- [ ] Non-admin users cannot access panel
- [ ] No conflicts with existing API routes

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| Auth conflict with Sanctum | Medium | Use separate 'web' guard for Filament |
| Route collision | Low | Filament uses `/admin` prefix |
| User model changes | Low | Only adds interface, no breaking changes |

## Security Considerations
- `canAccessPanel()` restricts access to admin role only
- Session-based auth for panel (separate from API tokens)
- CSRF protection middleware included

## Next Steps
→ Phase 02: Shield Authentication Setup
