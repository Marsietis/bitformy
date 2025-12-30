<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        $userHas2fa = auth()->user()->google2fa_secret !== null;

        return Inertia::render('settings/Password')->with('userHas2fa', $userHas2fa);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password_validator' => ['required', 'string'],
            'password_validator' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'salt' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
            'private_key' => ['required', 'string', 'regex:/\{"iv":"[A-Za-z0-9+\/=]+","ciphertext":"[A-Za-z0-9+\/=]+"\}/'],
        ]);

        $user = $request->user();

        $hashedCurrentPasswordValidator = hash('sha256', $validated['current_password_validator']);

        if ($hashedCurrentPasswordValidator !== $user->password_validator) {
            throw ValidationException::withMessages([
                'current_password_validator' => __('The provided password is incorrect.'),
            ]);
        }

        $hashedNewPasswordValidator = hash('sha256', $validated['password_validator']);

        $user->update([
            'password_validator' => $hashedNewPasswordValidator,
            'salt' => $validated['salt'],
            'private_key' => $validated['private_key'],
        ]);

        return back();
    }
}
