<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\MangaSeries;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * User model with manga reader extensions.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar
 * @property string|null $bio
 * @property string|null $profile_slug
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Chapter> $uploadedChapters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MangaSeries> $followedManga
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;

    use HasFactory;
    use HasRoles;
    use HasSlug;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'profile_slug',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Configure slug options.
     *
     * Why profile_slug?
     * - SEO-friendly URLs like /users/john-doe instead of /users/1
     * - Generated from user's name automatically
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('profile_slug');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * Chapters uploaded by this user.
     *
     * Why this relationship?
     * - Track who uploaded each chapter for moderation
     * - Show "Uploaded by" on chapter pages
     *
     * @return HasMany<Chapter, $this>
     */
    public function uploadedChapters(): HasMany
    {
        return $this->hasMany(Chapter::class, 'uploader_id');
    }

    /**
     * Manga series this user follows.
     *
     * Why this relationship?
     * - Users can follow manga to get notifications on new chapters
     * - Show personalized "Following" list
     *
     * @return BelongsToMany<MangaSeries, $this>
     */
    public function followedManga(): BelongsToMany
    {
        return $this->belongsToMany(MangaSeries::class, 'follows')
            ->withTimestamps();
    }
}
