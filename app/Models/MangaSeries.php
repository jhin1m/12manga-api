<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * MangaSeries model - the core entity of the manga reader.
 *
 * @property int $id
 * @property string $title
 * @property array<string, string|array<string>>|null $alt_titles
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property string|null $cover_image
 * @property int $views_count
 * @property string $average_rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Author> $authors
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Genre> $genres
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Chapter> $chapters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $followers
 */
class MangaSeries extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'alt_titles',
        'slug',
        'description',
        'status',
        'cover_image',
        'views_count',
        'average_rating',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Why these casts?
     * - alt_titles: JSON to PHP array for easy manipulation
     * - average_rating: Decimal string to proper decimal handling
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alt_titles' => 'array',
            'average_rating' => 'decimal:2',
        ];
    }

    /**
     * Configure slug options.
     *
     * Why slug from title?
     * - SEO-friendly URLs like /manga/one-piece instead of /manga/1
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    /**
     * Authors of this manga.
     *
     * Why belongsToMany?
     * - One manga can have multiple authors (writer, illustrator)
     *
     * @return BelongsToMany<Author, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_manga_series');
    }

    /**
     * Genres of this manga.
     *
     * Why belongsToMany?
     * - One manga can have multiple genres (Action, Adventure, etc.)
     *
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_manga_series');
    }

    /**
     * Chapters of this manga.
     *
     * @return HasMany<Chapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    /**
     * Users who follow this manga.
     *
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows')
            ->withTimestamps();
    }

    /**
     * Search manga by keyword.
     *
     * Why this scope?
     * - Searches in title (fulltext), description, and alt_titles (JSON)
     * - Fulltext for title/description, JSON_SEARCH for alt_titles
     *
     * How it works:
     * 1. MATCH...AGAINST for fulltext search on title/description
     * 2. JSON_SEARCH for searching within alt_titles JSON field
     * 3. Falls back to LIKE if not using MySQL
     *
     * @param Builder<MangaSeries> $query
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);

        if (empty($keyword)) {
            return $query;
        }

        // MySQL fulltext + JSON search
        if (config('database.default') === 'mysql') {
            return $query->where(function (Builder $q) use ($keyword) {
                // Fulltext search on title and description
                $q->whereRaw(
                    'MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)',
                    [$keyword]
                )
                // OR search in alt_titles JSON
                ->orWhereRaw(
                    'JSON_SEARCH(alt_titles, "one", ?, NULL, "$.*") IS NOT NULL',
                    ['%' . $keyword . '%']
                );
            });
        }

        // Fallback for SQLite/other databases: LIKE search
        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'LIKE', '%' . $keyword . '%')
                ->orWhere('description', 'LIKE', '%' . $keyword . '%')
                ->orWhere('alt_titles', 'LIKE', '%' . $keyword . '%');
        });
    }
}
