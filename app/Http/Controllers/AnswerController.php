<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Models\Form;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request, Form $form)
    {
        $submissionId = $form->generateSubmissionId();

        $form->answers()->createMany(array_map(function ($answer) use ($submissionId) {
            return [
                'question_id' => $answer['question_id'],
                'submission_id' => $submissionId,
                'answer' => $answer['answer'],
            ];
        }, $request->validated('answers')));

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
