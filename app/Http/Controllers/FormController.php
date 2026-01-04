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
            $questionType = $question->type instanceof QuestionType
                ? $question->type->value
                : (string) $question->type;

            $formattedOptions = [];
            if (! empty($question->options)) {
                $optionsArray = is_string($question->options) ? json_decode($question->options, true) : $question->options;
                if (is_array($optionsArray)) {
                    $optionId = 1;
                    foreach ($optionsArray as $optionText) {
                        $formattedOptions[] = [
                            'id' => $optionId++,
                            'text' => $optionText,
                        ];
                    }
                }
            }

            $questionData = [
                'id' => $question->id,
                'title' => $question->title,
                'type' => $questionType,
                'required' => (bool) $question->required,
                'order' => $question->order,
                'options' => $formattedOptions,
                'multipleChoice' => (bool) $question->allow_multiple,
                'rating_levels' => $question->rating_levels ?? 5,
            ];

            $processedQuestions[] = $questionData;
        }

        return Inertia::render('form/EditForm', [
            'form' => $form,
            'questions' => $processedQuestions,
            'questionTypes' => collect(QuestionType::cases())->map(fn ($case) => [
                'name' => $case->name,
                'value' => $case->value,
                'label' => $case->label(),
            ]),
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
            $optionsToSave = json_encode([]);
            if (isset($questionData['options']) && is_array($questionData['options'])) {
                $optionTexts = array_column($questionData['options'], 'text');
                $optionsToSave = json_encode($optionTexts);
            }

            $allowMultiple = $questionData['multipleChoice'] ?? false;
            $isRequired = $questionData['required'] ?? false;

            if (isset($questionData['id']) && in_array($questionData['id'], $currentQuestionIds)) {
                $existingQuestion = Question::find($questionData['id']);

                if ($existingQuestion) {
                    $existingQuestion->title = $questionData['title'];
                    $existingQuestion->type = $questionData['type'];
                    $existingQuestion->options = $optionsToSave;
                    $existingQuestion->allow_multiple = $allowMultiple;
                    $existingQuestion->required = $isRequired;
                    $existingQuestion->order = $questionOrder;
                    $existingQuestion->rating_levels = $questionData['rating_levels'] ?? null;
                    $existingQuestion->save();

                    $processedQuestionIds[] = $questionData['id'];
                }
            } else {
                $newQuestion = new Question;
                $newQuestion->form_id = $form->id;
                $newQuestion->title = $questionData['title'];
                $newQuestion->type = $questionData['type'];
                $newQuestion->options = $optionsToSave;
                $newQuestion->allow_multiple = $allowMultiple;
                $newQuestion->required = $isRequired;
                $newQuestion->order = $questionOrder;
                $newQuestion->rating_levels = $questionData['rating_levels'] ?? null;
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
