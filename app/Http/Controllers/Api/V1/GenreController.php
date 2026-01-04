<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Manga\Models\Genre;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\GenreResource;
use App\Http\Resources\MangaResource;
use Illuminate\Http\JsonResponse;

class GenreController extends ApiController
{
    /**
     * List all genres.
     */
    public function index(): JsonResponse
    {
        $genres = Genre::all();

        return $this->success(GenreResource::collection($genres));
    }

    /**
     * Show genre with paginated manga.
     */
    public function show(string $slug): JsonResponse
    {
        $genre = Genre::where('slug', $slug)->first();

        if (! $genre) {
            return $this->notFound('Genre not found');
        }

        $manga = $genre->mangaSeries()->paginate(15);

        return $this->success([
            'genre' => new GenreResource($genre),
            'manga' => MangaResource::collection($manga),
        ]);
    }
}
