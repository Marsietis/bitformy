<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Answer;
use App\Models\Form;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FormController extends Controller
{
    public function show($id)
    {
        return Inertia::render('form/ViewForm', [
            'form' => Form::with('questions')->findOrFail($id),
        ]);
    }

    public function store(StoreFormRequest $request)
    {
        $validatedData = $request->validated();

        $currentUserId = auth()->id();

        $form = new Form;
        $form->user_id = $currentUserId;
        $form->title = $validatedData['title'];
        $form->description = $validatedData['description'];
        $form->save();

        $questionOrder = 0;

        foreach ($validatedData['questions'] as $questionData) {
            $question = new Question;

            $question->form_id = $form->id;
            $question->title = $questionData['title'];
            $question->type = $questionData['type'];
            $question->order = $questionOrder;

            if (isset($questionData['required'])) {
                $question->required = $questionData['required'];
            } else {
                $question->required = false;
            }

            if ($questionData['type'] === 'choice') {
                if (! empty($questionData['options'])) {
                    $optionTexts = [];
                    foreach ($questionData['options'] as $option) {
                        $optionTexts[] = $option['text'];
                    }

                    $allowMultiple = false;
                    if (isset($questionData['multipleChoice'])) {
                        $allowMultiple = $questionData['multipleChoice'];
                    }

                    $optionsData = [
                        'items' => $optionTexts,
                        'multiple' => $allowMultiple,
                    ];
                    $question->options = json_encode($optionsData);
                }
            } else {
                $question->options = null;
            }

            $question->save();
            $questionOrder++;
        }

        return redirect()->route('dashboard')->with('success', 'Form created successfully');
    }

    public function edit(Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403);
        }

        $questions = Question::where('form_id', $form->id)
            ->orderBy('order')
            ->get();

        $processedQuestions = [];

        foreach ($questions as $question) {
            $questionData = [
                'id' => $question->id,
                'title' => $question->title,
                'type' => $question->type,
                'required' => $question->required,
                'order' => $question->order,
                'options' => [],
                'multipleChoice' => false,
            ];

            if ($question->type === 'choice') {
                $optionsJson = $question->options;

                if (! empty($optionsJson)) {
                    $optionsData = json_decode($optionsJson, true);

                    if ($optionsData && isset($optionsData['items'])) {
                        $formattedOptions = [];
                        $optionId = 1;

                        foreach ($optionsData['items'] as $optionText) {
                            $formattedOptions[] = [
                                'id' => $optionId,
                                'text' => $optionText,
                            ];
                            $optionId++;
                        }

                        $questionData['options'] = $formattedOptions;

                        if (isset($optionsData['multiple'])) {
                            $questionData['multipleChoice'] = $optionsData['multiple'];
                        }
                    }
                }
            }

            $processedQuestions[] = $questionData;
        }

        return Inertia::render('form/EditForm', [
            'form' => $form,
            'questions' => $processedQuestions,
        ]);
    }

    public function update(UpdateFormRequest $request, Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403);
        }

        $validatedData = $request->validated();

        $form->title = $validatedData['title'];
        $form->description = $validatedData['description'];
        $form->save();

        $currentQuestions = Question::where('form_id', $form->id)->get();
        $currentQuestionIds = [];
        foreach ($currentQuestions as $question) {
            $currentQuestionIds[] = $question->id;
        }

        $processedQuestionIds = [];

        $questionOrder = 0;
        foreach ($validatedData['questions'] as $questionData) {
            $optionsToSave = null;

            if ($questionData['type'] === 'choice') {
                if (isset($questionData['options'])) {
                    if (is_string($questionData['options']) && ! empty($questionData['options'])) {
                        $optionsToSave = $questionData['options'];
                    } elseif (is_array($questionData['options']) && ! empty($questionData['options'])) {
                        $optionTexts = [];
                        foreach ($questionData['options'] as $option) {
                            if (isset($option['text'])) {
                                $optionTexts[] = $option['text'];
                            }
                        }

                        $allowMultiple = false;
                        if (isset($questionData['multipleChoice'])) {
                            $allowMultiple = $questionData['multipleChoice'];
                        }

                        $optionsArray = [
                            'items' => $optionTexts,
                            'multiple' => $allowMultiple,
                        ];

                        $optionsToSave = json_encode($optionsArray);
                    }
                }
            }

            $isRequired = false;
            if (isset($questionData['required'])) {
                $isRequired = $questionData['required'];
            }

            if (isset($questionData['id']) && in_array($questionData['id'], $currentQuestionIds)) {
                $existingQuestion = Question::find($questionData['id']);

                if ($existingQuestion) {
                    $existingQuestion->title = $questionData['title'];
                    $existingQuestion->type = $questionData['type'];
                    $existingQuestion->options = $optionsToSave;
                    $existingQuestion->required = $isRequired;
                    $existingQuestion->order = $questionOrder;
                    $existingQuestion->save();

                    $processedQuestionIds[] = $questionData['id'];
                }
            } else {
                $newQuestion = new Question;
                $newQuestion->form_id = $form->id;
                $newQuestion->title = $questionData['title'];
                $newQuestion->type = $questionData['type'];
                $newQuestion->options = $optionsToSave;
                $newQuestion->required = $isRequired;
                $newQuestion->order = $questionOrder;
                $newQuestion->save();

                $processedQuestionIds[] = $newQuestion->id;
            }

            $questionOrder++;
        }

        $questionsToDelete = [];
        foreach ($currentQuestionIds as $currentId) {
            if (! in_array($currentId, $processedQuestionIds)) {
                $questionsToDelete[] = $currentId;
            }
        }

        if (! empty($questionsToDelete)) {
            Question::whereIn('id', $questionsToDelete)->delete();
        }

        return redirect()->route('dashboard')->with('success', 'Form updated successfully');
    }

    public function submit(Request $request, Form $form)
    {
        $requiredQuestions = [];
        $allFormQuestions = Question::where('form_id', $form->id)->get();

        foreach ($allFormQuestions as $question) {
            if ($question->required) {
                $requiredQuestions[] = $question->id;
            }
        }

        $submittedAnswers = $request->input('answers', []);

        if (empty($submittedAnswers)) {
            return redirect()->back()->withErrors(['answers' => 'Please answer at least one question']);
        }

        // Find all answered question IDs
        $answeredQuestionIds = [];
        foreach ($submittedAnswers as $answer) {
            if (isset($answer['question_id'])) {
                $answeredQuestionIds[] = $answer['question_id'];
            }
        }

        // Find missing required questions
        $missingRequiredQuestions = [];
        foreach ($requiredQuestions as $requiredId) {
            if (! in_array($requiredId, $answeredQuestionIds)) {
                $missingRequiredQuestions[] = $requiredId;
            }
        }

        if (! empty($missingRequiredQuestions)) {
            return redirect()->back()->withErrors(['answers' => 'Not all required questions have been answered.']);
        }

        foreach ($submittedAnswers as $answerData) {
            if (! isset($answerData['question_id'])) {
                return redirect()->back()->withErrors(['answers' => 'Each answer must have a question ID']);
            }

            if (empty($answerData['answer'])) {
                return redirect()->back()->withErrors(['answers' => 'Each answer must have text']);
            }

            $questionExists = Question::where('id', $answerData['question_id'])->exists();
            if (! $questionExists) {
                return redirect()->back()->withErrors(['answers' => 'Invalid question ID provided']);
            }

            $questionBelongsToForm = Question::where('id', $answerData['question_id'])
                ->where('form_id', $form->id)
                ->exists();
            if (! $questionBelongsToForm) {
                return redirect()->back()->withErrors(['answers' => 'Question does not belong to this form']);
            }
        }

        $submissionId = Str::uuid()->toString();

        foreach ($submittedAnswers as $answerData) {
            $newAnswer = new Answer;
            $newAnswer->question_id = $answerData['question_id'];
            $newAnswer->answer = $answerData['answer'];
            $newAnswer->form_id = $form->id;
            $newAnswer->submission_id = $submissionId;
            $newAnswer->save();
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

    public function regenerateLink(Form $form)
    {
        if (auth()->id() !== $form->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $oldId = $form->id;

        $newForm = Form::create([
            'user_id' => $form->user_id,
            'title' => $form->title,
            'description' => $form->description,
            'created_at' => $form->created_at,
            'updated_at' => $form->updated_at,
        ]);

        $newId = $newForm->id;

        $form->questions()->update(['form_id' => $newId]);
        $form->answers()->update(['form_id' => $newId]);

        Form::where('id', $oldId)->delete();

        return redirect()->route('form.edit', ['form' => $newId])
            ->with('success', 'New form link generated successfully');
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
