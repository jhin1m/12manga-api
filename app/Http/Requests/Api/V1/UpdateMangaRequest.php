<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMangaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'alt_titles' => ['sometimes', 'nullable', 'array'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['ongoing', 'completed', 'hiatus'])],
            'cover_image' => ['sometimes', 'nullable', 'string', 'url', 'regex:/^https?:\/\//i'],
            'author_ids' => ['sometimes', 'nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'genre_ids' => ['sometimes', 'nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cover_image.regex' => 'The cover image URL must use HTTP or HTTPS protocol only.',
        ];
    }
}
