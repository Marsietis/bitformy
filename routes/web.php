<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('forms/new', action: fn () => Inertia::render('form/CreateForm'))->name('forms.create');
    Route::post('forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('forms/{form}/answers', [AnswerController::class, 'show'])->name('answers.show');
    Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
    Route::post('forms/{form}/regenerate-link', [FormController::class, 'regenerateLink'])->name('forms.regenerate_link');
    Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
});

Route::get('forms/{id}', [FormController::class, 'show'])->name('forms.show');
Route::post('forms/{form}/answers', [AnswerController::class, 'store'])->name('answers.store');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
