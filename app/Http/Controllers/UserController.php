<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['roleRelation', 'classesCoordinated'])
            ->withCount('classesCoordinated');

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn ($sub) => $sub->where('nama', 'ilike', '%'.$search.'%')
                ->orWhere('email', 'ilike', '%'.$search.'%')
                ->orWhere('telepon', 'ilike', '%'.$search.'%')
                ->orWhere('lid', 'ilike', '%'.$search.'%'));
        }

        if (in_array($request->query('role'), ['admin', 'koordinator', 'orang_tua'], true)) {
            $roleId = User::resolveRoleId($request->query('role'));
            if ($roleId) {
                $query->where('id_role', $roleId);
            }
        }

        $users = $query->orderBy('nama')->paginate(12)->withQueryString();

        $allUsers = User::query()->with('roleRelation')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roleFilter' => $request->query('role'),
            'search' => $request->query('q'),
            'counts' => [
                'all' => $allUsers->count(),
                'admin' => $allUsers->filter(fn (User $u) => $u->isAdmin())->count(),
                'koordinator' => $allUsers->filter(fn (User $u) => $u->isCoordinator())->count(),
                'orang_tua' => $allUsers->filter(fn (User $u) => $u->isParent())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,koordinator,orang_tua'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:user,telepon'],
            'lid' => ['nullable', 'string', 'max:30', 'unique:user,lid'],
            'class_id' => ['nullable', Rule::exists('kelas', 'id_kelas')],
        ]);

        $user = User::create([
            'id_role' => User::resolveRoleId($data['role']),
            'nama' => $data['name'],
            'email' => $data['email'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'lid' => ! empty($data['lid']) ? $data['lid'] : null,
            'password' => Hash::make($data['password']),
        ]);

        if (! empty($data['class_id'])) {
            $user->classesCoordinated()->sync([$data['class_id']]);
        }

        if ($data['role'] === User::ROLE_PARENT) {
            \App\Models\Ortu::query()->firstOrCreate(
                ['nama' => $data['name']],
                [
                    'email' => $data['email'],
                    'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
                ]
            );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$data['name'].' berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $user->load('classesCoordinated');

        return view('admin.users.form', [
            'user' => $user,
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('user', 'email')->ignore($user->id_user, 'id_user')],
            'role' => ['required', 'in:admin,koordinator,orang_tua'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('user', 'telepon')->ignore($user->id_user, 'id_user')],
            'lid' => ['nullable', 'string', 'max:30', Rule::unique('user', 'lid')->ignore($user->id_user, 'id_user')],
            'class_id' => ['nullable', Rule::exists('kelas', 'id_kelas')],
        ]);

        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun sendiri.'])->withInput();
        }

        $user->update([
            'id_role' => User::resolveRoleId($data['role']),
            'nama' => $data['name'],
            'email' => $data['email'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'lid' => ! empty($data['lid']) ? $data['lid'] : null,
        ]);

        if (! empty($data['class_id'])) {
            $user->classesCoordinated()->sync([$data['class_id']]);
        } else {
            $user->classesCoordinated()->detach();
        }

        if ($data['role'] === User::ROLE_PARENT) {
            \App\Models\Ortu::query()->updateOrCreate(
                ['nama' => $data['name']],
                [
                    'email' => $data['email'],
                    'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
                ]
            );
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$user->name.' berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        return back()->with('info', 'Semua akun pada tabel user n8n berstatus aktif.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$name.' telah dihapus.');
    }
}
