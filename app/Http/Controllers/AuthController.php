<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login attempt via username/email + password.
     */
    public function login(Request $request)
{
    $request->validate([
        'login'    => 'required|string',
        'password' => 'required|string',
    ]);

    $throttleKey = Str::transliterate(Str::lower($request->input('login')) . '|' . $request->ip());

    // 1. Check rate limiter (5 max attempts per minute)
    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);

        return back()
            ->withInput($request->only('login', 'remember'))
            ->withErrors(['login' => "Too many login attempts. Please try again in {$seconds} seconds."]);
    }

    $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    $credentials = [
        $loginField => $request->login,
        'password'  => $request->password,
    ];

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $user = Auth::user();

        // 2. Ban status check
        if ($user->is_banned) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Your account has been banned. Reason: ' . ($user->ban_reason ?? 'Contact support.')]);
        }

        // 3. Clear limiter & regenerate session on verified login
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('freelancer.index'));
    }

    // Record failed attempt
    RateLimiter::hit($throttleKey, 60);

    return back()
        ->withInput($request->only('login', 'remember'))
        ->withErrors(['login' => 'Invalid username/email or password.']);
}

    /**
     * Show the "Set Password" form for newly registered users via Freelancer API.
     */
    public function showSetPassword()
    {
        // Only show if the user actually needs to set a password
        if (!session('needs_password_setup')) {
            return redirect()->route('freelancer.index');
        }

        return view('auth.set-password');
    }

    /**
     * Store the password chosen by the user after Freelancer API registration.
     */
    public function storePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        // Clear the flag
        $request->session()->forget('needs_password_setup');

        return redirect()
            ->route('freelancer.index')
            ->with('success', 'Password set successfully! You can now log in with your username/email and password.');
    }

    public function showChangePasswordForm() {
        return view('auth.change-password');
    }
    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => [
                'required', 
                'string', 
                'confirmed', 
                Password::min(8)->letters()->mixedCase()->numbers()->symbols()
            ],
        ]);

        $user = Auth::user();

        // Update the password directly
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Your password has been updated successfully!');
    }
    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
