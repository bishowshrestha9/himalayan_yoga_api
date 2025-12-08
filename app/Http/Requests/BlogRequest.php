<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];

        // For create, image is required. For update, it's optional
        if ($this->isMethod('post')) {
            $rules['image'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120'; // 5MB max
        } else {
            $rules['image'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'description.required' => 'Description is required',
            'image.required' => 'Image is required',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif, webp',
            'image.max' => 'The image may not be greater than 5MB',
            'is_active.required' => 'Status is required',
        ];
    }
}
