# Phase 1: Storage Abstraction

> **Goal**: Create interface and service for S3/local storage with auto-detection

## Context

**Problem**: ChapterImage model's `getUrlAttribute()` already switches between S3/local, but there's no service to handle uploads and deletions.

**Solution**: Create a storage service that:
- Uploads files to the correct disk based on `.env` config
- Organizes files by manga/chapter for easy cleanup
- Provides consistent URL generation

---

## Files to Create

### 1. ChapterImageStorageInterface

**Path**: `app/Domain/Manga/Contracts/ChapterImageStorageInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Manga\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Storage abstraction for chapter images.
 *
 * Why interface?
 * - Allows swapping implementations (S3, local, CDN)
 * - Easier testing with mocks
 * - Framework-agnostic domain code
 */
interface ChapterImageStorageInterface
{
    /**
     * Store multiple images for a chapter.
     *
     * @param array<UploadedFile> $files Images to upload
     * @param int $mangaId Parent manga ID
     * @param int $chapterId Chapter ID
     * @return array<int, string> Ordered array of stored paths
     */
    public function storeMany(array $files, int $mangaId, int $chapterId): array;

    /**
     * Delete multiple images from storage.
     *
     * @param array<string> $paths Paths to delete
     * @return bool True if all deleted successfully
     */
    public function deleteMany(array $paths): bool;

    /**
     * Delete all images for a chapter.
     *
     * @param int $mangaId Parent manga ID
     * @param int $chapterId Chapter ID
     * @return bool True if directory deleted
     */
    public function deleteChapterDirectory(int $mangaId, int $chapterId): bool;

    /**
     * Get public URL for a stored image.
     *
     * @param string $path Stored path
     * @return string Full URL
     */
    public function getUrl(string $path): string;

    /**
     * Get the active disk name.
     *
     * @return string 's3' or 'public'
     */
    public function getDiskName(): string;
}
```

---

### 2. ChapterImageStorageService

**Path**: `app/Domain/Manga/Services/ChapterImageStorageService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Manga\Services;

use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Handles chapter image storage operations.
 *
 * Storage organization:
 * - chapters/{manga_id}/{chapter_id}/001.jpg
 * - chapters/{manga_id}/{chapter_id}/002.png
 *
 * Why this structure?
 * - Easy to delete entire chapter (just delete directory)
 * - Manga-level grouping for potential batch operations
 * - Numeric prefixes preserve order
 */
class ChapterImageStorageService implements ChapterImageStorageInterface
{
    private Filesystem $disk;
    private string $diskName;

    public function __construct()
    {
        // Auto-detect disk from config
        // Uses 's3' if configured, falls back to 'public'
        $this->diskName = config('filesystems.default') === 's3' ? 's3' : 'public';
        $this->disk = Storage::disk($this->diskName);
    }

    /**
     * Store multiple images with auto-ordering.
     *
     * How ordering works:
     * - Files are numbered by their position in the array (001, 002, ...)
     * - Original filename extension is preserved
     * - Zero-padded to 3 digits (supports up to 999 pages)
     */
    public function storeMany(array $files, int $mangaId, int $chapterId): array
    {
        $basePath = $this->getChapterPath($mangaId, $chapterId);
        $storedPaths = [];

        foreach ($files as $index => $file) {
            // Validate it's an UploadedFile
            if (! $file instanceof UploadedFile) {
                continue;
            }

            // Generate ordered filename: 001.jpg, 002.png, etc.
            $order = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = "{$order}.{$extension}";

            // Store file
            $path = $this->disk->putFileAs($basePath, $file, $filename);

            if ($path) {
                $storedPaths[$index] = $path;
            }
        }

        return $storedPaths;
    }

    /**
     * Delete specific image files.
     */
    public function deleteMany(array $paths): bool
    {
        if (empty($paths)) {
            return true;
        }

        return $this->disk->delete($paths);
    }

    /**
     * Delete entire chapter directory.
     *
     * Why delete directory instead of individual files?
     * - Faster for many images
     * - Ensures no orphan files
     * - Cleaner storage
     */
    public function deleteChapterDirectory(int $mangaId, int $chapterId): bool
    {
        $path = $this->getChapterPath($mangaId, $chapterId);

        return $this->disk->deleteDirectory($path);
    }

    /**
     * Get public URL for image path.
     */
    public function getUrl(string $path): string
    {
        return $this->disk->url($path);
    }

    /**
     * Get current disk name.
     */
    public function getDiskName(): string
    {
        return $this->diskName;
    }

    /**
     * Generate consistent path for chapter images.
     */
    private function getChapterPath(int $mangaId, int $chapterId): string
    {
        return "chapters/{$mangaId}/{$chapterId}";
    }
}
```

---

## Service Provider Registration

**File**: `app/Providers/AppServiceProvider.php`

Add to `register()` method:

```php
use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use App\Domain\Manga\Services\ChapterImageStorageService;

public function register(): void
{
    // Bind storage interface to concrete implementation
    $this->app->bind(
        ChapterImageStorageInterface::class,
        ChapterImageStorageService::class
    );
}
```

---

## Common Pitfalls

1. **Forgetting to run `php artisan storage:link`**: Required for local storage to work with public URLs
2. **S3 CORS issues**: Ensure S3 bucket allows uploads from your domain
3. **Missing S3 credentials**: Check `.env` has all `AWS_*` variables set
4. **File permissions**: Local storage directory must be writable

---

## Verification Steps

```bash
# 1. Create the interface file
# 2. Create the service file
# 3. Update AppServiceProvider
# 4. Verify binding works:
php artisan tinker
>>> app(App\Domain\Manga\Contracts\ChapterImageStorageInterface::class)
# Should return ChapterImageStorageService instance
```

---

## Key Takeaways

- **Interface first**: Always define contracts before implementations
- **Auto-detection**: Service reads config once at construction
- **Consistent paths**: Use predictable directory structure for easy cleanup
- **Zero-padding**: Prevents ordering issues (1, 10, 2 vs 001, 002, 010)
