<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Form;
use App\Models\Question;
use App\Http\Requests\StoreAnswerRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request, Form $form)
    {
        $answers = $request->validated('answers');

        foreach ($answers as $answerData) {

            $questionBelongsToForm = Question::where('id', $answerData['question_id'])
                ->where('form_id', $form->id)
                ->exists();
            if (! $questionBelongsToForm) {
                return redirect()->back()->withErrors(['answers' => 'Question does not belong to this form']);
            }
        }

        $submissionId = Str::uuid()->toString();

        foreach ($answers as $answerData) {
            $newAnswer = new Answer;
            $newAnswer->question_id = $answerData['question_id'];
            $newAnswer->answer = $answerData['answer'];
            $newAnswer->form_id = $form->id;
            $newAnswer->submission_id = $submissionId;
            $newAnswer->save();
        }

        return redirect()->back()->with('success', 'Form submitted successfully!');
    }

    public function show(Form $form)
    {
        Gate::authorize('view', $form);

        return Inertia::render('form/Answers', [
            'answers' => $form->answers()->get(),
            'questions' => $form->questions()->orderBy('order')->get(),
            'form' => $form,
        ]);
    }
}
