<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('form/new', action: fn () => Inertia::render('form/CreateForm'));
    Route::post('form', [FormController::class, 'store'])->name('form.store');
    Route::get('form/{form}/answers', [FormController::class, 'answers'])->name('form.answers');
    Route::get('form/{form}/edit', [FormController::class, 'edit'])->name('form.edit');
    Route::put('form/{form}', [FormController::class, 'update'])->name('form.update');
    Route::post('form/{form}/regenerate-link', [FormController::class, 'regenerateLink'])->name('form.regenerateLink');
    Route::delete('form/{form}', [FormController::class, 'destroy'])->name('form.destroy');
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::get('/2fa/recovery-keys', [TwoFactorController::class, 'generateRecoveryKeys'])->name('2fa.recovery-keys');
});

Route::get('form/{id}', [FormController::class, 'show'])->name('form.view');
Route::post('form/{form}/submit', [FormController::class, 'submit'])->name('form.submit');

Route::get('/2fa/verify', [TwoFactorController::class, 'verifyForm'])->name('2fa.verify.form');
Route::post('/2fa/verify', [TwoFactorController::class, 'verifyCode'])->name('2fa.verify');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
