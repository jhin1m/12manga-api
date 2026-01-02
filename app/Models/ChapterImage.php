<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * ChapterImage model - represents a single page image in a chapter.
 *
 * @property int $id
 * @property int $chapter_id
 * @property int $order
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Chapter $chapter
 * @property-read string $url
 */
class ChapterImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'chapter_id',
        'order',
        'path',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * Why append url?
     * - API responses should include the full URL, not just path
     *
     * @var list<string>
     */
    protected $appends = ['url'];

    /**
     * Chapter this image belongs to.
     *
     * @return BelongsTo<Chapter, $this>
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Get the full URL to this image.
     *
     * Why accessor instead of storing URL?
     * - Flexibility: Can change storage disk without database migration
     * - Storage path is more portable than full URL
     *
     * How it works:
     * - Uses S3 disk by default (configured in config/filesystems.php)
     * - Falls back to public disk if S3 not configured
     */
    public function getUrlAttribute(): string
    {
        // Use S3 if configured, otherwise fall back to public disk
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';

        return Storage::disk($disk)->url($this->path);
    }
}
