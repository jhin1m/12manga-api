<?php

declare(strict_types=1);

namespace App\Domain\Manga\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Genre model - represents a manga genre/category.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MangaSeries> $mangaSeries
 */
class Genre extends Model
{
    use HasFactory;
    use HasSlug;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Configure slug options.
     *
     * Why slug from name?
     * - SEO-friendly URLs like /genres/slice-of-life
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Manga series in this genre.
     *
     * Why belongsToMany?
     * - One genre contains many manga
     * - One manga can have multiple genres
     *
     * @return BelongsToMany<MangaSeries, $this>
     */
    public function mangaSeries(): BelongsToMany
    {
        return $this->belongsToMany(MangaSeries::class, 'genre_manga_series');
    }
}
