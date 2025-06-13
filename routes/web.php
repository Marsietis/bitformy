<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('form/new', action: fn() => Inertia::render('form/CreateForm'));
    Route::post('form', [FormController::class, 'store'])->name('form.store');
});

Route::get('form/{id}', [FormController::class, 'show'])->name('form.view');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
