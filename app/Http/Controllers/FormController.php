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
        $form = Form::with('user:id,public_key')->findOrFail($id);
        $questions = $form->questions()->orderBy('order')->get();

        return Inertia::render('form/ViewForm', [
            'form' => $form,
            'questions' => $questions,
        ]);
    }

    public function edit(Form $form)
    {
        $questions = $form->questions()->orderBy('order')->get()->map(function ($question) {
            // Transform options for frontend if they exist
            if ($question->options && $question->type === 'choice') {
                $optionsData = json_decode($question->options, true);
                if ($optionsData && isset($optionsData['items'])) {
                    $question->options = collect($optionsData['items'])->map(function ($item, $index) {
                        return [
                            'id' => $index + 1,
                            'text' => $item
                        ];
                    })->values()->toArray();
                    
                    // Set multipleChoice flag from options
                    $question->multipleChoice = $optionsData['multiple'] ?? false;
                } else {
                    $question->options = [];
                    $question->multipleChoice = false;
                }
            } else {
                $question->options = [];
                $question->multipleChoice = false;
            }
            
            return $question;
        });

        return Inertia::render('form/EditForm', [
            'form' => $form,
            'questions' => $questions,
        ]);
    }

    public function update(Request $request, Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403, 'Unauthorized action.');
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.form_id' => 'nullable|string',
            'questions.*.title' => 'required|string|max:255',
            'questions.*.type' => 'required|string|in:text,choice',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'nullable',
            'questions.*.multipleChoice' => 'nullable|boolean',
            'questions.*.order' => 'nullable|integer',
            'questions.*.created_at' => 'nullable|string',
            'questions.*.updated_at' => 'nullable|string',
        ]);

        $form->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        // Get existing question IDs to track which ones to keep
        $existingQuestionIds = $form->questions()->pluck('id')->toArray();
        $updatedQuestionIds = [];

        foreach ($validated['questions'] as $index => $questionData) {
            $options = null;

            if ($questionData['type'] === 'choice') {
                if (is_string($questionData['options']) && !empty($questionData['options'])) {
                    // Options already stored as JSON string (existing question)
                    $options = $questionData['options'];
                } elseif (is_array($questionData['options']) && !empty($questionData['options'])) {
                    // Options as array (new question or modified options)
                    $options = json_encode([
                        'items' => collect($questionData['options'])->pluck('text')->toArray(),
                        'multiple' => $questionData['multipleChoice'] ?? false,
                    ]);
                }
            }

            // Check if this is an existing question or a new one
            if (isset($questionData['id']) && in_array($questionData['id'], $existingQuestionIds)) {
                // Update existing question
                $question = $form->questions()->find($questionData['id']);
                $question->update([
                    'title' => $questionData['title'],
                    'type' => $questionData['type'],
                    'options' => $options,
                    'required' => $questionData['required'] ?? false,
                    'order' => $index,
                ]);
                $updatedQuestionIds[] = $questionData['id'];
            } else {
                // Create new question
                $question = $form->questions()->create([
                    'title' => $questionData['title'],
                    'type' => $questionData['type'],
                    'options' => $options,
                    'required' => $questionData['required'] ?? false,
                    'order' => $index,
                ]);
                $updatedQuestionIds[] = $question->id;
            }
        }

        // Delete questions that were removed
        $questionsToDelete = array_diff($existingQuestionIds, $updatedQuestionIds);
        if (!empty($questionsToDelete)) {
            $form->questions()->whereIn('id', $questionsToDelete)->delete();
        }

        return redirect()->route('dashboard')->with('success', 'Form updated successfully');
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

    public function destroy(Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $form->delete();

        return redirect()->route('dashboard')->with('success', 'Form deleted successfully');
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
