<?php

declare(strict_types=1);

namespace App\Domain\Manga\Services;

use App\Domain\Manga\Contracts\ChapterImageStorageInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
