<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Manga\Models\MangaSeries
 */
class MangaResource extends JsonResource
{
    /**
     * Transform the manga series resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'alt_titles' => $this->alt_titles,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'cover_image' => $this->cover_image,
            'views_count' => $this->views_count,
            'average_rating' => $this->average_rating,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Conditional relationships (only when loaded)
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'chapters_count' => $this->whenCounted('chapters'),
        ];
    }
}
