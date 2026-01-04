<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Image validation:
     * - array: Multiple files in batch upload
     * - max:100: Limit to 100 pages per chapter
     * - file: Must be an actual uploaded file
     * - image: Must be a valid image type
     * - mimes: Restrict to common web formats
     * - max:5120: 5MB per image (5 * 1024 KB)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],

            // Image upload validation (replaces path-based approach)
            'images' => ['nullable', 'array', 'max:100'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120', // 5MB per image
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'number.required' => 'Chapter number is required.',
            'number.numeric' => 'Chapter number must be a valid number.',
            'number.min' => 'Chapter number must be at least 0.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'Maximum 100 images per chapter.',
            'images.*.file' => 'Each image must be a valid file upload.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be JPEG, PNG, WebP, or GIF.',
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
    }
}
