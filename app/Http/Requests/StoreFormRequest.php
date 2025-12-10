<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => ['required', 'string', new Enum(QuestionType::class)],
            'questions.*.required' => 'required|boolean',
            'questions.*.options' => 'array|nullable|required_if:questions.*.type,choice',
            'questions.*.options.*.text' => 'sometimes|string|max:255',
            'questions.*.multipleChoice' => 'boolean|nullable|required_if:questions.*.type,choice',
            'questions.*.rating_levels' => 'integer|nullable|min:1|max:10|required_if:questions.*.type,rating',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
