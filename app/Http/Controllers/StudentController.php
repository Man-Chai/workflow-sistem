<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ManagesClassData;

    public function store(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $this->assertCanManageClass($classRoom->id);

        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:students,nis'],
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'parent_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_PARENT)),
            ],
        ]);

        $classRoom->students()->create($data);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', 'Siswa '.$data['name'].' berhasil ditambahkan ke kelas.');
    }

    public function edit(Student $student): View
    {
        $this->assertCanManageClass($student->class_id);

        return view('admin.students.form', [
            'student' => $student,
            'parents' => User::query()->where('role', User::ROLE_PARENT)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->assertCanManageClass($student->class_id);

        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($student->id)],
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'parent_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_PARENT)),
            ],
        ]);

        $student->update($data);

        return redirect()
            ->route('classes.show', $student->class_id)
            ->with('success', 'Data siswa '.$student->name.' berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->assertCanManageClass($student->class_id);
        $classId = $student->class_id;
        $name = $student->name;

        $student->delete();

        return redirect()
            ->route('classes.show', $classId)
            ->with('success', 'Siswa '.$name.' telah dihapus dari kelas.');
    }
}
