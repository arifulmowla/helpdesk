<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeBaseIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Knowledge base is public, so always authorize.
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
            'search' => ['sometimes', 'string', 'max:255'],
            'tag' => ['sometimes', 'string', 'exists:tags,slug'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
