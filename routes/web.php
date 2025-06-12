<?php

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('meetings/{id}', [FormController::class, 'show'])->name('meeting.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', action: fn() => Inertia::render('Dashboard'))
        ->name('dashboard');
    Route::get('form/new', action: fn() => Inertia::render('form/CreateForm'));
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
