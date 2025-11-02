<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\AllRequiredQuestionsAnswered;

class StoreAnswerRequest extends FormRequest
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
            'answers' => ['required', 'array', 'min:1', new AllRequiredQuestionsAnswered($this->route('form')->id)],
            'answers.*.question_id' => 'required|integer|distinct|exists:questions,id',
            'answers.*.answer' => 'required|string|regex:/^-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----$/s',
        ];
    }
}
