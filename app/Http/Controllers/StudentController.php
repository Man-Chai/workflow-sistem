<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\ClassRoom;
use App\Models\Ortu;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'ortu' ? 'ortu' : 'siswa';
        $search = $request->filled('q') ? $request->string('q')->trim()->toString() : null;
        $filter = $request->query('filter');

        $studentsQuery = Student::query()->with(['ortu', 'classRoom']);
        if ($search && $tab === 'siswa') {
            $studentsQuery->where(fn ($sub) => $sub->where('nama', 'ilike', '%'.$search.'%')
                ->orWhereHas('ortu', fn ($o) => $o->where('nama', 'ilike', '%'.$search.'%')
                    ->orWhere('telepon', 'ilike', '%'.$search.'%')
                    ->orWhere('email', 'ilike', '%'.$search.'%'))
                ->orWhereHas('classRoom', fn ($c) => $c->where('nama', 'ilike', '%'.$search.'%')));
        }
        if ($tab === 'siswa') {
            if ($filter === 'assigned') {
                $studentsQuery->whereNotNull('id_kelas');
            } elseif ($filter === 'unassigned') {
                $studentsQuery->whereNull('id_kelas');
            }
        }
        $students = $studentsQuery->orderBy('nama')->paginate(12, ['*'], 'siswa_page')->withQueryString();

        $parentsQuery = Ortu::query()->with('students.classRoom')->withCount('students');
        if ($search && $tab === 'ortu') {
            $parentsQuery->where(fn ($sub) => $sub->where('nama', 'ilike', '%'.$search.'%')
                ->orWhere('telepon', 'ilike', '%'.$search.'%')
                ->orWhere('email', 'ilike', '%'.$search.'%')
                ->orWhereHas('students', fn ($s) => $s->where('nama', 'ilike', '%'.$search.'%')));
        }
        $parents = $parentsQuery->orderBy('nama')->paginate(12, ['*'], 'ortu_page')->withQueryString();

        return view('admin.students.index', [
            'tab' => $tab,
            'search' => $search,
            'filter' => $filter,
            'students' => $students,
            'parents' => $parents,
            'allParents' => Ortu::query()->orderBy('nama')->get(),
            'allClasses' => ClassRoom::query()->orderBy('nama')->get(),
            'totalStudents' => Student::query()->count(),
            'assignedStudents' => Student::query()->whereNotNull('id_kelas')->count(),
            'unassignedStudents' => Student::query()->whereNull('id_kelas')->count(),
            'totalParents' => Ortu::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.form', [
            'student' => null,
            'parents' => Ortu::query()->orderBy('nama')->get(),
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function storeMaster(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('ortu', 'id_ortu')],
            'parent_name' => ['nullable', 'string', 'max:100'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:100'],
            'class_id' => ['nullable', Rule::exists('kelas', 'id_kelas')],
        ]);

        $ortuId = $data['parent_id'] ?? null;

        if (! $ortuId && ! empty($data['parent_name'])) {
            $ortu = Ortu::create([
                'nama' => $data['parent_name'],
                'telepon' => ! empty($data['parent_phone']) ? $data['parent_phone'] : null,
                'email' => ! empty($data['parent_email']) ? $data['parent_email'] : null,
            ]);
            $ortuId = $ortu->id_ortu;
        }

        Student::create([
            'nama' => $data['name'],
            'id_ortu' => $ortuId ?: null,
            'id_kelas' => ! empty($data['class_id']) ? $data['class_id'] : null,
        ]);

        return redirect()
            ->route('students.index', ['tab' => 'siswa'])
            ->with('success', 'Data siswa '.$data['name'].' berhasil ditambahkan.');
    }

    /**
     * Separate page to assign one or multiple existing students from the checklist into a class.
     */
    public function assignForm(ClassRoom $classRoom): View
    {
        $this->assertCanManageClass($classRoom->id_kelas);

        $availableStudents = Student::query()
            ->with(['ortu', 'classRoom'])
            ->where(function ($q) use ($classRoom): void {
                $q->whereNull('id_kelas')
                    ->orWhere('id_kelas', '!=', $classRoom->id_kelas);
            })
            ->orderByRaw('CASE WHEN id_kelas IS NULL THEN 0 ELSE 1 END')
            ->orderBy('nama')
            ->get();

        return view('admin.classes.assign-students', [
            'classRoom' => $classRoom,
            'availableStudents' => $availableStudents,
        ]);
    }

    /**
     * Assign one or multiple existing students from the checklist into a class.
     */
    public function store(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $this->assertCanManageClass($classRoom->id_kelas);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', Rule::exists('siswa', 'id_siswa')],
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa dari daftar checklist.',
            'student_ids.min' => 'Pilih minimal satu siswa dari daftar checklist.',
        ]);

        $count = Student::query()
            ->whereIn('id_siswa', $data['student_ids'])
            ->update(['id_kelas' => $classRoom->id_kelas]);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', $count.' siswa berhasil ditambahkan ke kelas '.$classRoom->name.'.');
    }

    /**
     * Unassign a student from a class (sets id_kelas = null without deleting the student record).
     */
    public function unassign(Student $student): RedirectResponse
    {
        if ($student->id_kelas) {
            $this->assertCanManageClass($student->id_kelas);
        }

        $classId = $student->id_kelas;
        $name = $student->name;

        $student->update(['id_kelas' => null]);

        if ($classId) {
            return redirect()
                ->route('classes.show', $classId)
                ->with('success', 'Siswa '.$name.' telah dilepas dari kelas (data siswa tetap tersimpan di Daftar Siswa & Ortu).');
        }

        return redirect()
            ->route('students.index')
            ->with('success', 'Siswa '.$name.' telah dilepas dari kelas.');
    }

    public function edit(Student $student): View
    {
        if (! auth()->user()->isAdmin() && $student->id_kelas) {
            $this->assertCanManageClass($student->id_kelas);
        }

        return view('admin.students.form', [
            'student' => $student,
            'parents' => Ortu::query()->orderBy('nama')->get(),
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        if (! auth()->user()->isAdmin() && $student->id_kelas) {
            $this->assertCanManageClass($student->id_kelas);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('ortu', 'id_ortu')],
            'class_id' => ['nullable', Rule::exists('kelas', 'id_kelas')],
        ]);

        $student->update([
            'nama' => $data['name'],
            'id_ortu' => ! empty($data['parent_id']) ? $data['parent_id'] : null,
            'id_kelas' => ! empty($data['class_id']) ? $data['class_id'] : null,
        ]);

        if ($request->query('from') === 'class' && $student->id_kelas) {
            return redirect()
                ->route('classes.show', $student->id_kelas)
                ->with('success', 'Data siswa '.$student->name.' berhasil diperbarui.');
        }

        return redirect()
            ->route('students.index', ['tab' => 'siswa'])
            ->with('success', 'Data siswa '.$student->name.' berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        if (! auth()->user()->isAdmin() && $student->id_kelas) {
            $this->assertCanManageClass($student->id_kelas);
        }

        $name = $student->name;
        $student->delete();

        return redirect()
            ->route('students.index', ['tab' => 'siswa'])
            ->with('success', 'Data siswa '.$name.' telah dihapus permanen.');
    }

    public function createParent(): View
    {
        return view('admin.students.parent-form', [
            'parent' => null,
        ]);
    }

    public function storeParent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
        ]);

        Ortu::create([
            'nama' => $data['name'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'email' => ! empty($data['email']) ? $data['email'] : null,
        ]);

        return redirect()
            ->route('students.index', ['tab' => 'ortu'])
            ->with('success', 'Data orang tua '.$data['name'].' berhasil ditambahkan.');
    }

    public function editParent(Ortu $ortu): View
    {
        $ortu->load('students.classRoom');

        return view('admin.students.parent-form', [
            'parent' => $ortu,
        ]);
    }

    public function updateParent(Request $request, Ortu $ortu): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
        ]);

        $ortu->update([
            'nama' => $data['name'],
            'telepon' => ! empty($data['phone']) ? $data['phone'] : null,
            'email' => ! empty($data['email']) ? $data['email'] : null,
        ]);

        return redirect()
            ->route('students.index', ['tab' => 'ortu'])
            ->with('success', 'Data orang tua '.$ortu->name.' berhasil diperbarui.');
    }

    public function destroyParent(Ortu $ortu): RedirectResponse
    {
        $name = $ortu->name;

        // Unlink children first since id_ortu is nullable
        Student::query()->where('id_ortu', $ortu->id_ortu)->update(['id_ortu' => null]);

        $ortu->delete();

        return redirect()
            ->route('students.index', ['tab' => 'ortu'])
            ->with('success', 'Data orang tua '.$name.' telah dihapus.');
    }
}
