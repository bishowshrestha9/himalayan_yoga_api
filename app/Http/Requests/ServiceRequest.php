<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
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
        
        $rules = [
            'title' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'slug' => $isUpdate ? 'sometimes|required|string|unique:services,slug,' . $this->route('id') : 'required|string|unique:services,slug',
            'description' => $isUpdate ? 'sometimes|required|string' : 'required|string',
            'yoga_type' => $isUpdate ? 'sometimes|required|in:basic,intermediate,advanced' : 'required|in:basic,intermediate,advanced',
            'benefits' => 'nullable',
            'class_schedule' => 'nullable',
            'session_time' => 'nullable|string|max:255',
            'instructor_id' => $isUpdate ? 'sometimes|required|exists:instructors,id' : 'required|exists:instructors,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'price' => $isUpdate ? 'sometimes|required|numeric|min:0' : 'required|numeric|min:0',
            'capacity' => $isUpdate ? 'sometimes|required|integer|min:1' : 'required|integer|min:1',
            'currency' => 'nullable|in:USD,NRS,GBP,INR',
        ];
        
        // After prepareForValidation runs, validate array items
        if (is_array($this->benefits)) {
            $rules['benefits.*'] = 'string';
        }
        
        if (is_array($this->class_schedule)) {
            $rules['class_schedule.*'] = 'string';
        }
        
        return $rules;
    }
    
    public function prepareForValidation()
    {
        $data = [];
        
        // Benefits comes as comma-separated string
        if ($this->has('benefits')) {
            if (is_string($this->benefits)) {
                $data['benefits'] = array_map('trim', explode(',', $this->benefits));
            }
        }
        
        // Class schedule comes as JSON string
        if ($this->has('class_schedule')) {
            if (is_string($this->class_schedule)) {
                $decoded = json_decode($this->class_schedule, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Ensure all values are strings
                    $data['class_schedule'] = array_map(function($item) {
                        return is_string($item) ? $item : json_encode($item);
                    }, $decoded);
                }
            }
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }
}
