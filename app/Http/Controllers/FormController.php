<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use App\Models\Question;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class FormController extends Controller
{
    public function show($id)
    {
        return Inertia::render('form/ViewForm', [
            'form' => Form::with(['questions', 'user:id,public_key'])->findOrFail($id),
        ]);
    }

    public function create()
    {
        return Inertia::render('form/CreateForm', [
            'questionTypes' => collect(QuestionType::cases())->map(fn ($case) => [
                'name' => $case->name,
                'value' => $case->value,
                'label' => $case->label(),
            ]),
        ]);
    }

    public function store(StoreFormRequest $request)
    {

        $form = auth()->user()->forms()->create($request->only(['title', 'description']));
        $form->addQuestions($request->validated('questions'));

        return redirect()->route('dashboard')->with('success', 'Form created successfully');
    }

    public function edit(Form $form)
    {
        Gate::authorize('update', $form);

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
        Gate::authorize('update', $form);

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

    public function destroy(Form $form)
    {
        Gate::authorize('delete', $form);

        $form->delete();

        return redirect()->route('dashboard')->with('success', 'Form deleted successfully');
    }
}
