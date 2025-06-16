<?php
namespace App\Http\Controllers;
use App\Models\Form;
use Inertia\Inertia;
use App\Models\Answer;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $forms = Form::where('user_id', $user->id)->get();
        $formCount = $forms->count();
        $answersCount = Answer::whereIn('form_id', $forms->pluck('id'))->count();
        
        return Inertia::render('Dashboard', [
            'forms' => $forms,
            'formCount' => $formCount,
            'answersCount' => $answersCount,
            'user' => $user,
        ]);
    }
}