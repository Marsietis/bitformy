<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password_validator' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'salt' => 'required|string|size:64|regex:/^[a-f0-9]{64}$/',
            'public_key' => 'required|string|min:200|max:5000|regex:/^-----BEGIN PGP PUBLIC KEY BLOCK-----.*-----END PGP PUBLIC KEY BLOCK-----$/s',
            'private_key' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password_validator' => $request->password_validator,
            'salt' => $request->salt,
            'private_key' => $request->private_key,
            'public_key' => $request->public_key,
        ]);


        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
