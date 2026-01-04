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
     * @param  array<UploadedFile>  $files  Images to upload
     * @param  int  $mangaId  Parent manga ID
     * @param  int  $chapterId  Chapter ID
     * @return array<int, string> Ordered array of stored paths
     */
    public function storeMany(array $files, int $mangaId, int $chapterId): array;

    /**
     * Delete multiple images from storage.
     *
     * @param  array<string>  $paths  Paths to delete
     * @return bool True if all deleted successfully
     */
    public function deleteMany(array $paths): bool;

    /**
     * Delete all images for a chapter.
     *
     * @param  int  $mangaId  Parent manga ID
     * @param  int  $chapterId  Chapter ID
     * @return bool True if directory deleted
     */
    public function deleteChapterDirectory(int $mangaId, int $chapterId): bool;

    /**
     * Get public URL for a stored image.
     *
     * @param  string  $path  Stored path
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
