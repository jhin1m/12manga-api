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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*.path' => ['required_with:images', 'string'],
            'images.*.order' => ['required_with:images', 'integer', 'min:0'],
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
            'images.*.path.required_with' => 'Each image must have a path.',
            'images.*.order.required_with' => 'Each image must have an order.',
        ];
    }
}
