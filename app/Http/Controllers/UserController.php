<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount(['children', 'classesCoordinated']);

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn ($sub) => $sub->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
        }

        if (in_array($request->query('role'), ['admin', 'koordinator', 'orang_tua'], true)) {
            $query->where('role', $request->query('role'));
        }

        $users = $query->orderBy('role')->orderBy('name')->paginate(12)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roleFilter' => $request->query('role'),
            'search' => $request->query('q'),
            'counts' => [
                'all' => User::query()->count(),
                'admin' => User::query()->where('role', 'admin')->count(),
                'koordinator' => User::query()->where('role', 'koordinator')->count(),
                'orang_tua' => User::query()->where('role', 'orang_tua')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,koordinator,orang_tua'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::create([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$data['name'].' berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,koordinator,orang_tua'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun sendiri.'])->withInput();
        }

        if ($user->is($request->user()) && ! $request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.'])->withInput();
        }

        $roleChangedFromCoordinator = $user->isCoordinator() && $data['role'] !== 'koordinator';

        $user->update([
            ...$data,
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($roleChangedFromCoordinator) {
            ClassRoom::query()->where('coordinator_id', $user->id)->update(['coordinator_id' => null]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$user->name.' berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Status akun '.$user->name.' diperbarui menjadi '.($user->is_active ? 'aktif' : 'nonaktif').'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Administrator terakhir tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Akun '.$name.' telah dihapus.');
    }
}
