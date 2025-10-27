<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user->google2fa_secret) {
            $user->google2fa_secret = Google2FA::generateSecretKey();
            $user->save();
        }

        $secret = $user->google2fa_secret;
        $qrImage = $this->generateQRCode($user->email, $secret);

        return Inertia::render('auth/TwoFactor/Setup', [
            'qrImage' => $qrImage,
            'secret' => $secret,
        ]);
    }

    private function generateQRCode($email, $secret)
    {
        $qrContent = Google2FA::getQRCodeUrl(
            config('app.name'),
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrContent));
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function setup(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $google2fa = app('pragmarx.google2fa');

        if ($google2fa->verifyKey(auth()->user()->google2fa_secret, $request->otp)) {
            return redirect('/dashboard')->with('success', '2FA enabled successfully.');
        }

        return back()->withErrors(['otp' => 'Invalid verification code.']);
    }

    public function verifyForm(Request $request)
    {
        // Check if user is in the 2FA flow
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactor/Verify');
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verifyCode(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        // Check if user is in the 2FA flow
        if (!$request->session()->has('2fa_user_id')) {
            return back()->withErrors(['otp' => 'Session expired. Please log in again.']);
        }

        $userId = $request->session()->get('2fa_user_id');
        $user = \App\Models\User::findOrFail($userId);

        if (Google2FA::verifyKey($user->google2fa_secret, $request->otp)) {
            // Remove temporary session data
            $request->session()->forget('2fa_user_id');

            // Log the user in
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'private_key' => $user->private_key,
                'redirect_url' => route('dashboard', absolute: false),
            ]);
        }

        return back()->withErrors(['otp' => 'Invalid verification code.']);
    }
}
