<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Manga\Models\Chapter;
use App\Domain\Manga\Models\ChapterImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Manga\Models\ChapterImage>
 */
class ChapterImageFactory extends Factory
{
    protected $model = ChapterImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'order' => fake()->numberBetween(1, 50),
            'path' => 'manga/test/chapter-1/page-'.fake()->numberBetween(1, 50).'.jpg',
        ];
    }
}
