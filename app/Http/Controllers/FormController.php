<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
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

        return Inertia::render('form/EditForm', [
            'form' => $form,
            'questions' => $form->getFormattedQuestions(),
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

        $form->update($request->only(['title', 'description']));
        $form->updateQuestions($request->validated('questions'));

        return redirect()->route('dashboard')->with('success', 'Form updated successfully');
    }

    public function destroy(Form $form)
    {
        Gate::authorize('delete', $form);

        $form->delete();

        return redirect()->route('dashboard')->with('success', 'Form deleted successfully');
    }
}
