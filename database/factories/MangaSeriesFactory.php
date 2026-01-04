<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Manga\Models\MangaSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Manga\Models\MangaSeries>
 */
class MangaSeriesFactory extends Factory
{
    protected $model = MangaSeries::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'alt_titles' => [
                'en' => fake()->sentence(3),
                'ja' => 'テスト',
            ],
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['ongoing', 'completed', 'hiatus']),
            'cover_image' => null,
            'views_count' => fake()->numberBetween(100, 100000),
            'average_rating' => fake()->randomFloat(2, 3.0, 5.0),
        ];
    }

    /**
     * Indicate that the manga is ongoing.
     */
    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ongoing',
        ]);
    }

    /**
     * Indicate that the manga is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
