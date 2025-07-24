<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-knowledge-base');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:tags,name,' . $this->route('tag')->id,
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $this->route('tag')->id,
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The tag name is required.',
            'name.unique' => 'A tag with this name already exists.',
            'slug.unique' => 'A tag with this slug already exists.',
        ];
    }
}
