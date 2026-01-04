<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\ChapterController;
use App\Http\Controllers\Api\V1\GenreController;
use App\Http\Controllers\Api\V1\MangaController;
use Grazulex\ApiRoute\Facades\ApiRoute;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API routes are versioned using grazulex/laravel-apiroute.
| Supports URI path, header, query, and Accept header detection.
| See config/apiroute.php for configuration options.
|
*/

// Version 1 - Current stable version
ApiRoute::version('v1', function () {
    // Public routes with auth rate limiter (5/min - brute force protection)
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
    });

    // Protected routes with authenticated rate limiter (120/min)
    Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');
    });

    // Public manga routes
    Route::get('manga', [MangaController::class, 'index'])->name('api.v1.manga.index');
    Route::get('manga/popular', [MangaController::class, 'popular'])->name('api.v1.manga.popular');
    Route::get('manga/latest', [MangaController::class, 'latest'])->name('api.v1.manga.latest');
    Route::get('manga/search', [MangaController::class, 'search'])->name('api.v1.manga.search');
    Route::get('manga/{slug}', [MangaController::class, 'show'])->name('api.v1.manga.show');

    // Public genre routes
    Route::get('genres', [GenreController::class, 'index'])->name('api.v1.genres.index');
    Route::get('genres/{slug}', [GenreController::class, 'show'])->name('api.v1.genres.show');

    // Public author routes
    Route::get('authors', [AuthorController::class, 'index'])->name('api.v1.authors.index');
    Route::get('authors/{slug}', [AuthorController::class, 'show'])->name('api.v1.authors.show');

    // Public chapter routes (nested under manga)
    Route::get('manga/{slug}/chapters', [ChapterController::class, 'index'])->name('api.v1.chapters.index');
    Route::get('manga/{slug}/chapters/{number}', [ChapterController::class, 'show'])->name('api.v1.chapters.show');

    // Admin manga routes (requires authentication and admin role)
    Route::middleware(['auth:sanctum', 'throttle:authenticated', 'role:admin'])->group(function () {
        Route::post('manga', [MangaController::class, 'store'])->name('api.v1.manga.store');
        Route::put('manga/{slug}', [MangaController::class, 'update'])->name('api.v1.manga.update');
        Route::delete('manga/{slug}', [MangaController::class, 'destroy'])->name('api.v1.manga.destroy');

        // Admin chapter routes (nested under manga)
        Route::post('manga/{slug}/chapters', [ChapterController::class, 'store'])->name('api.v1.chapters.store');
        Route::put('manga/{slug}/chapters/{number}', [ChapterController::class, 'update'])->name('api.v1.chapters.update');
        Route::delete('manga/{slug}/chapters/{number}', [ChapterController::class, 'destroy'])->name('api.v1.chapters.destroy');

        // Chapter moderation routes
        Route::get('chapters/pending', [ChapterController::class, 'pending'])->name('api.v1.chapters.pending');
        Route::post('chapters/{chapter}/approve', [ChapterController::class, 'approve'])->name('api.v1.chapters.approve');
    });
})
    ->current()
    ->rateLimit(60); // Global rate limit: 60 requests/minute for v1
