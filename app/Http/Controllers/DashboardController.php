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

        return Inertia::render('Dashboard', [
            'forms' => $forms,
            'user' => $user,
        ]);
    }
}
