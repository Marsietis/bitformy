<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.form_id' => 'nullable|string',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:text,choice',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'nullable|required_if:questions.*.type,choice',
            'questions.*.multipleChoice' => 'nullable|boolean',
            'questions.*.order' => 'nullable|integer',
            'questions.*.created_at' => 'nullable|string',
            'questions.*.updated_at' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
