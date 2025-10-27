<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\FakeSalt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Random\RandomException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Check if user has 2FA enabled
        if ($user->google2fa_secret) {
            // Mark user as partially authenticated
            $request->session()->put('2fa_user_id', $user->id);
            Auth::logout();

            return response()->json([
                'success' => true,
                'requires_2fa' => true,
                'redirect_url' => route('2fa.verify.form'),
            ]);
        }

        return response()->json([
            'success' => true,
            'private_key' => $user->private_key,
            'redirect_url' => route('dashboard', absolute: false),
        ]);
    }

    /**
     * @throws RandomException
     */
    public function pullSalt(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Return the user's salt if the user exists
        if ($user) {
            return response()->json([
                'salt' => $user->salt,
            ]);
        }

        // If user doesn't exist, check if there is already a fake salt for this email
        $fakeSalt = FakeSalt::where('email', $request->email)->first();

        if ($fakeSalt) {
            return response()->json([
                'salt' => $fakeSalt->salt,
            ]);
        }

        // If no fake salt exists, create and store a new one
        $randomSalt = bin2hex(random_bytes(32));

        FakeSalt::create([
            'email' => $request->email,
            'salt' => $randomSalt,
        ]);

        return response()->json([
            'salt' => $randomSalt,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
