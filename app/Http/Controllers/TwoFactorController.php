<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
            return redirect('/2fa/recovery-keys')->with('success', '2FA enabled successfully.');
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

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required'],
        ]);

        $hashedPassword = \Hash::make($request->password);

        if(!$hashedPassword === $request->password) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = $request->user();
        $user->google2fa_secret = null;
        $user->recoveryKeys()->delete();
        $user->save();

        return redirect()->back()->with('success', '2FA disabled successfully.');
    }

    public function generateRecoveryKeys(Request $request)
    {
        $user = $request->user();

        // Only generate recovery keys if 2FA is enabled
        if (!$user->google2fa_secret) {
            return redirect()->back()->withErrors(['2fa' => 'Two-Factor Authentication is not enabled.']);
        }

        // Delete existing recovery keys
        $user->recoveryKeys()->delete();

        $recoveryKeysArray = [];

        for ($i = 0; $i < 8; $i++) {
            $plainKey = strtoupper(Str::random(8) . '-' . Str::random(8));

            $user->recoveryKeys()->create([
                'recovery_key' => bcrypt($plainKey),
            ]);

            // Keep the plain version to show to the user
            $recoveryKeysArray[] = $plainKey;
        }

        return Inertia::render('auth/TwoFactor/RecoveryKeys', [
            'recoveryKeys' => $recoveryKeysArray,
        ]);
    }

    public function recoverAccount(Request $request)
    {
        $request->validate([
            'recovery_key' => 'required|string',
        ]);

        // Check if user is in the 2FA flow
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $userId = $request->session()->get('2fa_user_id');
        $user = \App\Models\User::findOrFail($userId);

        $recoveryKeyRecord = $user->recoveryKeys()
            ->get()
            ->first(function ($key) use ($request) {
                return \Hash::check($request->recovery_key, $key->recovery_key);
            });

        if ($recoveryKeyRecord) {
            // Delete the used recovery key
            $recoveryKeyRecord->delete();

            // Remove temporary session data
            $request->session()->forget('2fa_user_id');

            // Log the user in
            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/settings/security')->with('success', 'Logged in using recovery key.');
        }

        return back()->withErrors(['recovery_key' => 'Invalid recovery key.']);
    }

    public function showRecoveryForm(Request $request)
    {
        // Check if user is in the 2FA flow
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactor/Recover');
    }
}
