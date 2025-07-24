<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeBaseArticleRequest extends FormRequest
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
        $articleId = $this->route('article')->id ?? null;

        return [
            'title' => ['required', 'max:255'],
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('knowledge_base_articles', 'slug')->ignore($articleId),
            ],
            'body' => ['required', 'array', 'min:1'],
            'body.*' => ['required'], // Validate TipTap editor content has minimum blocks
            'tags' => ['array'],
            'tags.*.id' => ['nullable', 'exists:tags,id'],
            'tags.*.name' => ['required_without:tags.*.id', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The article title is required.',
            'title.max' => 'The article title may not be greater than 255 characters.',
            'slug.required' => 'The article slug is required.',
            'slug.alpha_dash' => 'The slug may only contain letters, numbers, dashes and underscores.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'slug.max' => 'The slug may not be greater than 255 characters.',
            'body.required' => 'The article content is required.',
            'body.array' => 'The article content must be in a valid format.',
            'body.min' => 'The article content must contain at least one block.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.exists' => 'One or more selected tags do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure slug is provided or generate from title
        if (!$this->has('slug') || empty($this->slug)) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title ?? ''),
            ]);
        }

        // Ensure is_published is a boolean
        if ($this->has('is_published')) {
            $this->merge([
                'is_published' => filter_var($this->is_published, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
