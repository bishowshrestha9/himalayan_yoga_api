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
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string boolean values to actual booleans
        if ($this->has('is_active')) {
            $value = $this->input('is_active');
            
            // Convert string "true"/"false" or "1"/"0" to boolean
            if (is_string($value)) {
                $this->merge([
                    'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Convert content JSON string to array if needed
        if ($this->has('content')) {
            $content = $this->input('content');
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge(['content' => $decoded]);
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Check if this is an update request (has id in route)
        $isUpdate = $this->route('id') !== null;
        
        $rules = [
            'title' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => $isUpdate ? 'sometimes|required|string' : 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'author' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'content.*.heading' => 'required|string',
            'content.*.paragraph' => 'required|string',
            'conclusion' => 'nullable|string',
            'is_active' => $isUpdate ? 'sometimes|required|boolean' : 'required|boolean',
            'slug' => $isUpdate ? 'sometimes|required|string|unique:blogs,slug,' . $this->route('id') : 'required|string|unique:blogs,slug',
        ];

        // For create, image is required. For update, it's optional
        if ($isUpdate) {
            $rules['image'] = 'nullable|file|image|mimes:jpeg,jpg,png,gif,webp|max:5120';
        } else {
            $rules['image'] = 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:5120';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'description.required' => 'Description is required',
            'image.required' => 'Image is required',
            'slug.required' => 'Slug is required',
            'image.file' => 'The image must be a file',
            'image.image' => 'The uploaded file must be an image (jpeg, jpg, png, gif, webp)',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif, webp',
            'image.max' => 'The image may not be greater than 5MB',
            'is_active.required' => 'Status is required',
            'is_active.boolean' => 'Status must be a boolean (true or false)',
            'content.*.heading.required' => 'Each content section must have a heading',
            'content.*.paragraph.required' => 'Each content section must have a paragraph',
        ];
    }
}