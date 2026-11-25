<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($credentials['email'])])
            ->first();

        $validPassword = false;

        if ($user && ! empty($user->password)) {
            $storedPassword = (string) $user->password;

            if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
                try {
                    $validPassword = Hash::check($credentials['password'], $storedPassword);
                } catch (RuntimeException) {
                    $validPassword = false;
                }
            } else {
                // Auto-upgrade any legacy plain-text string to Bcrypt hash
                if (hash_equals($storedPassword, $credentials['password'])) {
                    $validPassword = true;
                    $user->forceFill(['password' => Hash::make($credentials['password'])])->save();
                }
            }
        }

        if (! $user || ! $validPassword) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        Auth::login($user);
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user->touch();

        return redirect()
            ->intended(route('dashboard'))
            ->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari KorlasSync.');
    }
}
