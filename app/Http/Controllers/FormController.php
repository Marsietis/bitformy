<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FormController extends Controller
{
    public function index()
    {

    }

    public function create()
    {

    }

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
}
