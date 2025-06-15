<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FormController extends Controller
{
    public function show($id)
    {
        $form = Form::findOrFail($id);
        $questions = $form->questions()->orderBy('order')->get();

        return Inertia::render('form/ViewForm', [
            'form' => $form,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:text,choice',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'array|required_if:questions.*.type,choice',
            'questions.*.options.*.text' => 'string|max:255',
            'questions.*.multipleChoice' => 'boolean',
        ]);

        $form = Form::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $options = null;

            if ($questionData['type'] === 'choice' && !empty($questionData['options'])) {
                $options = json_encode([
                    'items' => collect($questionData['options'])->pluck('text')->toArray(),
                    'multiple' => $questionData['multipleChoice'] ?? false,
                ]);
            }

            $form->questions()->create([
                'title' => $questionData['title'],
                'type' => $questionData['type'],
                'options' => $options,
                'required' => $questionData['required'] ?? false,
                'order' => $index,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Form created successfully');
    }

    public function submit(Request $request, Form $form)
    {
        $validated = $request->validate([
            'answers' => [
                'required',
                'array',
                function ($attribute, $value, $fail) use ($form) {
                    $requiredQuestionIds = $form->questions()->where('required', true)->pluck('id');
                    $submittedQuestionIds = collect($value)->pluck('question_id');
                    $missingRequiredQuestions = $requiredQuestionIds->diff($submittedQuestionIds);

                    if ($missingRequiredQuestions->isNotEmpty()) {
                        $fail('Not all required questions have been answered.');
                    }
                },
            ],
            'answers.*.question_id' => [
                'required',
                'exists:questions,id',
                function ($attribute, $value, $fail) use ($form) {
                    if (!$form->questions()->where('id', $value)->exists()) {
                        $fail("The selected {$attribute} is invalid for this form.");
                    }
                },
            ],
            'answers.*.answer' => 'required|string',
        ]);

        $submissionId = Str::uuid();

        foreach ($validated['answers'] as $answerData) {
            $form->answers()->create([
                'question_id' => $answerData['question_id'],
                'answer' => $answerData['answer'],
                'form_id' => $form->id,
                'submission_id' => $submissionId,
            ]);
        }

        return redirect()->back()->with('success', 'Form submitted successfully!');
    }

    public function answers(Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $answers = $form->answers()->get();
        $questions = $form->questions()->orderBy('order')->get();
        return Inertia::render('form/Answers', ['answers' => $answers, 'questions' => $questions, 'form' => $form]);
    }
}
