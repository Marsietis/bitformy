<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Question;

class AllRequiredQuestionsAnswered implements ValidationRule
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

        $hasUnansweredRequiredQuestions = Question::where('form_id', $this->formId)
            ->where('required', true)
            ->whereNotIn('id', $answeredIds)
            ->exists();

        if ($hasUnansweredRequiredQuestions) {
            $fail('Not all required questions have been answered.');
        }
    }

}
