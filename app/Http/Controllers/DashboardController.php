<?php

namespace App\Http\Controllers;

use App\Models\Form;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Dashboard', [
            'forms' => Form::where('user_id', auth()->user()->id)->latest()->get(),
        ]);
    }
}
