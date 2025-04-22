<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
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
            "name"=>"required|string",
            "price"=>"required|integer",
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'description' => 'nullable|string|max:1000',
            'content'=>'nullable|file|mimes:pdf,doc,docx|max:30720',
            'provider_id'=>'required|exists:providers,id',
            'field_id'=>'required|exists:fields,id'
        ];
    }
}
