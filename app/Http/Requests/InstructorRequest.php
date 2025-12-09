<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstructorRequest extends FormRequest
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
        $isUpdate = $this->route('id') !== null;
        
        return [
            'name' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'profession' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'experience' => $isUpdate ? 'sometimes|required|integer|min:0' : 'required|integer|min:0',
            'bio' => 'nullable|string',
            'specialities' => 'nullable|string',
            'certifications' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ];
    }
    
    public function prepareForValidation()
    {
        if ($this->has('certifications') && is_string($this->certifications)) {
            // Convert comma-separated string to JSON array
            $certifications = array_map('trim', explode(',', $this->certifications));
            $this->merge([
                'certifications' => $certifications,
            ]);
        }
    }
}
