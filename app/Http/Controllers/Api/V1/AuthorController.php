<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Manga\Models\Author;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\MangaResource;
use Illuminate\Http\JsonResponse;

class AuthorController extends ApiController
{
    /**
     * List all authors.
     */
    public function index(): JsonResponse
    {
        $authors = Author::all();

        return $this->success(AuthorResource::collection($authors));
    }

    /**
     * Show author with paginated manga.
     */
    public function show(string $slug): JsonResponse
    {
        $author = Author::where('slug', $slug)->first();

        if (! $author) {
            return $this->notFound('Author not found');
        }

        $manga = $author->mangaSeries()->paginate(15);

        return $this->success([
            'author' => new AuthorResource($author),
            'manga' => MangaResource::collection($manga),
        ]);
    }
}
