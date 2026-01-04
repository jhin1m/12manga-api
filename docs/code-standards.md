# Code Standards & Conventions

## 🏗️ DDD Lite Architecture
We follow **Domain-Driven Design (DDD) Lite**. The goal is to separate business logic from the framework and delivery mechanism.

### Principles:
1.  **Domain Isolation**: Business rules live in `app/Domain`. They should not know about HTTP requests or sessions.
2.  **Thin Controllers**: Controllers in `app/Http` only handle request routing, validation calls, and response triggering.
3.  **Action Pattern**: For complex write operations (e.g., creating a Manga with multiple authors and genres), use an `Action` class.
4.  **Service Layer**: For read operations or logic that spans multiple models within a domain, use a `Service`.

## 💻 Coding Conventions

### Naming
- **Classes**: `PascalCase` (e.g., `MangaService`).
- **Methods**: `camelCase` (e.g., `updateProfile`).
- **Variables**: `camelCase`.
- **Database Tables/Columns**: `snake_case`.
- **Routes**: `kebab-case` (e.g., `/api/v1/manga-series`).

### Type Hinting
- **Always** use property, parameter, and return type hints.
- Use `void` for methods that return nothing.
- Use `?Type` for nullable returns.

```php
public function follow(User $user, MangaSeries $manga): void
{
    // ...
}
```

## 📍 Where to Place New Code
- **New Feature**: Identify which Domain it belongs to. If none fits, create a new one in `app/Domain`.
- **Database Logic**: Eloquent Models in `app/Domain/{Domain}/Models`.
- **Complex Logic**: `app/Domain/{Domain}/Actions` (for writes) or `app/Domain/{Domain}/Services` (for general logic).
- **API Endpoint**: `app/Http/Controllers/Api/V1` + `routes/api.php`.
- **Validation**: `app/Http/Requests/Api/V1`.

## 🔐 Authorization & Security

### Role-Based Access Control (RBAC)
We use `spatie/laravel-permission` coupled with a custom `EnsureUserHasRole` middleware.

- **Middleware**: Use `role:admin` or `role:user` in route definitions.
- **Pattern**: Apply authorization at the routing layer for simple access control, and use Policies for more granular, model-specific logic.

```php
ApiRoute::version('v1', function () {
    // Public routes
    ApiRoute::get('manga', [MangaController::class, 'index']);

    // Admin protected routes
    ApiRoute::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        ApiRoute::post('manga', [MangaController::class, 'store']);
    });
});
```

### Input Sanitization & Validation
- **URL Whitelisting**: When accepting external URLs (e.g., `cover_image`), always validate the protocol to prevent SSRF or XSS.
- **Example**: `['cover_image' => ['nullable', 'url', 'regex:/^https?:\/\//i']]`

### Routing Patterns
- **Nested Resources**: Use nested routing for child entities (e.g., chapters under manga) to provide clear context.
- **Example**: `/api/v1/manga/{slug}/chapters/{number}`

## 🧪 Testing Standards
- We use **Pest PHP**.
- **Naming**: Test files should end in `Test.php`.
- **Coverage**: Every new Action or Service MUST have a corresponding test.
- **Database**: Use `RefreshDatabase` trait to ensure clean state.
- **Assertions**: Use Pest's functional expectations (e.g., `expect($user->name)->toBe('John')`).

## 🛠️ Tooling
- **Pint**: Use Laravel Pint for automated code styling. Run `./vendor/bin/pint` before committing.
- **Scramble**: API documentation is auto-generated. Ensure your Controllers have proper type hints and PHPDoc for better docs.

## ❓ Unresolved Questions
- Should we enforce Strict Types (`declare(strict_types=1);`) in all files?
- What is the threshold for moving logic from a Service to an Action?
