<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:text,choice',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'array|required_if:questions.*.type,choice',
            'questions.*.options.*.text' => 'string|max:255',
            'questions.*.multipleChoice' => 'boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
