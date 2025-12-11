<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
        return [
            'userName' => 'required|string|max:255',
            'userEmail' => 'required|email|max:255',
            'service_id' => 'required|exists:services,id',
            'fromDate' => 'required|string',
            'toDate' => 'required|string',
            'time' => 'required|string',
            'status' => 'required|in:confirmed,pending,cancelled',
            'participants' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            //
        ];
    }
}
