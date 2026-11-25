<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Ortu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:user,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:user,telepon'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.',
            'phone.unique' => 'Nomor telepon ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'id_role' => User::resolveRoleId(User::ROLE_PARENT),
            'nama' => $data['name'],
            'email' => $data['email'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'password' => $data['password'],
        ]);

        Ortu::query()->firstOrCreate(
            ['nama' => $data['name']],
            [
                'email' => $data['email'],
                'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            ]
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di KorlasSync, '.$user->name.'!');
    }
}
