<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Form;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function index() {}

    public function create() {}

    public function store(Request $request, Form $form)
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

    public function show(Form $form)
    {
        if (auth()->user()->cannot('view', $form)) {
            abort(403);
        }
        $answers = $form->answers()->get();
        $questions = $form->questions()->orderBy('order')->get();

        return Inertia::render('form/Answers', ['answers' => $answers, 'questions' => $questions, 'form' => $form]);
    }

    public function edit($id) {}

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
