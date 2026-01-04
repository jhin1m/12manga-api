<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
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
     * All fields optional - partial updates allowed.
     * If images provided, they replace all existing images.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['sometimes', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],

            // Optional: Replace all images
            'images' => ['nullable', 'array', 'max:100'],
            'images.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
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
