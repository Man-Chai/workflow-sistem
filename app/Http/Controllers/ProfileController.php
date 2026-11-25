<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('classesCoordinated');

        return view('profile.edit', ['user' => $user]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('user', 'email')->ignore($user->id_user, 'id_user')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('user', 'telepon')->ignore($user->id_user, 'id_user')],
            'lid' => ['nullable', 'string', 'max:30', Rule::unique('user', 'lid')->ignore($user->id_user, 'id_user')],
        ]);

        $user->update([
            'nama' => $data['name'],
            'email' => $data['email'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'lid' => ! empty($data['lid']) ? $data['lid'] : null,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }
}
