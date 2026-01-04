<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Manga\Models\Chapter
 */
class ChapterResource extends JsonResource
{
    /**
     * Transform the chapter resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'slug' => $this->slug,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Conditional relationships (only when loaded)
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'images' => ChapterImageResource::collection($this->whenLoaded('images')),
            'manga' => new MangaResource($this->whenLoaded('mangaSeries')),
        ];
    }
}
