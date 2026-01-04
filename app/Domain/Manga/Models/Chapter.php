<?php

declare(strict_types=1);

namespace App\Domain\Manga\Models;

use App\Domain\User\Models\User;
use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Chapter model - represents a single chapter of a manga.
 *
 * @property int $id
 * @property int $manga_series_id
 * @property int $uploader_id
 * @property string $number
 * @property string|null $title
 * @property string $slug
 * @property bool $is_approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read MangaSeries $mangaSeries
 * @property-read User $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ChapterImage> $images
 */
class Chapter extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChapterFactory
    {
        return ChapterFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'manga_series_id',
        'uploader_id',
        'number',
        'title',
        'slug',
        'is_approved',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Why these casts?
     * - number: String to decimal for proper handling of .5 chapters
     * - is_approved: Integer to boolean for cleaner PHP code
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'decimal:2',
            'is_approved' => 'boolean',
        ];
    }

    /**
     * Configure slug options.
     *
     * Why slug from manga title + number?
     * - Creates readable URLs like /chapters/one-piece-1 or /chapters/naruto-100.5
     * - Includes manga title for SEO context
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function (Chapter $model): string {
                // Get manga title if loaded, otherwise use manga_series_id
                $mangaTitle = $model->mangaSeries?->title ?? 'manga';

                return $mangaTitle.' '.$model->number;
            })
            ->saveSlugsTo('slug');
    }

    /**
     * Manga series this chapter belongs to.
     *
     * @return BelongsTo<MangaSeries, $this>
     */
    public function mangaSeries(): BelongsTo
    {
        return $this->belongsTo(MangaSeries::class);
    }

    /**
     * User who uploaded this chapter.
     *
     * Why track uploader?
     * - For moderation: know who to contact if issues
     * - For credit: show "Uploaded by" on chapter pages
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * Images (pages) of this chapter.
     *
     * @return HasMany<ChapterImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ChapterImage::class)->orderBy('order');
    }

    /**
     * Scope: Only approved chapters.
     *
     * Why this scope?
     * - Public API should only show approved chapters
     * - Admins can see all chapters via without this scope
     *
     * @param  Builder<Chapter>  $query
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Only pending (not approved) chapters.
     *
     * Why this scope?
     * - Admin moderation queue shows only unapproved chapters
     *
     * @param  Builder<Chapter>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }
}
