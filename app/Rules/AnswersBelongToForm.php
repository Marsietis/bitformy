<?php

namespace App\Rules;

use App\Models\Question;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AnswersBelongToForm implements ValidationRule
{
    public function __construct(protected string $formId) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $answeredIds = collect($value)->pluck('question_id');

        $allAnswersBelongToForm = Question::where('form_id', $this->formId)
            ->whereIn('id', $answeredIds)
            ->exists();

        if (! $allAnswersBelongToForm) {
            $fail('Some answers do not belong to this form.');
        }
    }
}
