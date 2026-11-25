<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\ClassRoom;
use App\Models\Ortu;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $query = ClassRoom::query()->with(['coordinators', 'grupWa'])->withCount('students');

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where('nama', 'ilike', '%'.$search.'%');
        }

        $classes = $query->orderBy('nama')->paginate(12)->withQueryString();

        $rows = $classes->getCollection()->map(fn (ClassRoom $class) => [
            'class' => $class,
            'balance' => $class->cashBalance(),
        ]);

        return view('admin.classes.index', [
            'classes' => $classes,
            'rows' => $rows,
            'search' => $request->query('q'),
            'totalClasses' => ClassRoom::query()->count(),
            'totalStudents' => Student::query()->count(),
            'unassigned' => ClassRoom::query()->doesntHave('coordinators')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.classes.form', [
            'classRoom' => null,
            'coordinators' => $this->coordinators(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateClass($request);

        $classRoom = ClassRoom::create([
            'nama' => $data['name'],
        ]);

        if (! empty($data['coordinator_id'])) {
            $classRoom->coordinators()->sync([$data['coordinator_id']]);
        }

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$data['name'].' berhasil dibuat.');
    }

    public function show(ClassRoom $classRoom): View
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->canManageClass($classRoom->id_kelas), 403);

        $classRoom->load(['coordinators', 'students.ortu', 'grupWa']);

        $availableStudents = Student::query()
            ->with(['ortu', 'classRoom'])
            ->where(function ($q) use ($classRoom): void {
                $q->whereNull('id_kelas')
                    ->orWhere('id_kelas', '!=', $classRoom->id_kelas);
            })
            ->orderByRaw('CASE WHEN id_kelas IS NULL THEN 0 ELSE 1 END')
            ->orderBy('nama')
            ->get();

        return view('admin.classes.show', [
            'classRoom' => $classRoom,
            'dues' => collect(),
            'balance' => $classRoom->cashBalance(),
            'parents' => Ortu::query()->orderBy('nama')->get(),
            'availableStudents' => $availableStudents,
        ]);
    }

    public function edit(ClassRoom $classRoom): View
    {
        $classRoom->load('coordinators');

        return view('admin.classes.form', [
            'classRoom' => $classRoom,
            'coordinators' => $this->coordinators(),
        ]);
    }

    public function update(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $data = $this->validateClass($request, $classRoom);

        $classRoom->update([
            'nama' => $data['name'],
        ]);

        if (! empty($data['coordinator_id'])) {
            $classRoom->coordinators()->sync([$data['coordinator_id']]);
        } else {
            $classRoom->coordinators()->detach();
        }

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$classRoom->name.' berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        if ($classRoom->students()->exists()) {
            return back()->with('error', 'Kelas masih memiliki siswa terdaftar. Hapus atau pindahkan siswa terlebih dahulu.');
        }

        $name = $classRoom->name;
        $classRoom->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$name.' beserta seluruh data terkait telah dihapus.');
    }

    private function coordinators()
    {
        return User::query()
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClass(Request $request, ?ClassRoom $classRoom = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelas', 'nama')->ignore($classRoom?->id_kelas, 'id_kelas'),
            ],
            'coordinator_id' => [
                'nullable',
                Rule::exists('user', 'id_user'),
            ],
        ]);
    }
}
