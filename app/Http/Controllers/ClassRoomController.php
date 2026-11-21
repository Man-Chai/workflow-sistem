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

class ClassRoomController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $query = ClassRoom::query()->with('coordinator')->withCount('students');

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn ($sub) => $sub->where('name', 'like', '%'.$search.'%')->orWhere('academic_year', 'like', '%'.$search.'%'));
        }

        $classes = $query->orderBy('name')->paginate(9)->withQueryString();

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
            'unassigned' => ClassRoom::query()->whereNull('coordinator_id')->count(),
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

        ClassRoom::create($data);

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$data['name'].' berhasil dibuat.');
    }

    public function show(ClassRoom $classRoom): View
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->canManageClass($classRoom->id), 403);

        $classRoom->load(['coordinator', 'students.parent']);

        $dues = $classRoom->dues()->with('creator')->orderByDesc('due_date')->get()->map(function ($due) use ($classRoom) {
            $target = $due->targetAmount($classRoom->students->count());
            $verified = $due->verifiedAmount();

            return [
                'due' => $due,
                'target' => $target,
                'verified' => $verified,
                'percent' => $target > 0 ? min(100, (int) round($verified / $target * 100)) : 0,
            ];
        });

        return view('admin.classes.show', [
            'classRoom' => $classRoom,
            'dues' => $dues,
            'balance' => $classRoom->cashBalance(),
            'parents' => User::query()->where('role', User::ROLE_PARENT)->orderBy('name')->get(),
        ]);
    }

    public function edit(ClassRoom $classRoom): View
    {
        return view('admin.classes.form', [
            'classRoom' => $classRoom,
            'coordinators' => $this->coordinators(),
        ]);
    }

    public function update(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update($this->validateClass($request, $classRoom));

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$classRoom->name.' berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $name = $classRoom->name;
        $classRoom->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', 'Kelas '.$name.' beserta seluruh data terkait telah dihapus.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function coordinators()
    {
        return User::query()
            ->where('role', User::ROLE_KOORDINATOR)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClass(Request $request, ?ClassRoom $classRoom = null): array
    {
        $academicYear = $request->input('academic_year');

        return $request->validate([
            'name' => [
                'required',
                'string',
                Rule::in(ClassRoom::LEVELS),
                Rule::unique('classes')
                    ->where(fn ($query) => $query->where('academic_year', $academicYear))
                    ->ignore($classRoom?->id),
            ],
            'academic_year' => ['required', 'string', 'max:20'],
            'coordinator_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_KOORDINATOR)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
