<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $forms = Form::where('user_id', $user->id)->orderBy('updated_at', 'desc')->get();

        return Inertia::render('Dashboard', [
            'forms' => $forms,
            'user' => $user,
        ]);
    }
}
