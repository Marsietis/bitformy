<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password_validator' => 'required|string|size:64|regex:/^[a-f0-9]+$/',
            'salt' => 'required|string|size:64|regex:/^[a-f0-9]+$/',
            'public_key' => 'required|string|min:200|max:1000|regex:/^-----BEGIN PGP PUBLIC KEY BLOCK-----.*-----END PGP PUBLIC KEY BLOCK-----$/s',
            'private_key' => 'required|string|regex:/\{"iv":"[A-Za-z0-9+\/=]+","ciphertext":"[A-Za-z0-9+\/=]+"\}/',
        ]);

        $hashedPasswordValidator = hash('sha256', $request->password_validator);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password_validator' => $hashedPasswordValidator,
            'salt' => $request->salt,
            'private_key' => $request->private_key,
            'public_key' => $request->public_key,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
