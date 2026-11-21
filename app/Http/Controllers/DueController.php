<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\ClassRoom;
use App\Models\Due;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DueController extends Controller
{
    use ManagesClassData;

    public function create(): View
    {
        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        abort_if($classes->isEmpty(), 403, 'Belum ada kelas yang dapat dikelola.');

        return view('kas.due-form', ['classes' => $classes, 'due' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classId = $this->managedClassId($request);
        $data = $this->validateDue($request);

        $due = Due::create([
            ...$data,
            'class_id' => $classId,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('cash.index', ['class' => $classId, 'tab' => 'iuran'])
            ->with('success', 'Iuran "'.$due->title.'" berhasil dibuat.');
    }

    public function edit(Due $due): View
    {
        $this->assertCanManageClass($due->class_id);

        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        return view('kas.due-form', ['classes' => $classes, 'due' => $due]);
    }

    public function update(Request $request, Due $due): RedirectResponse
    {
        $this->assertCanManageClass($due->class_id);

        $due->update($this->validateDue($request));

        return redirect()
            ->route('cash.index', ['class' => $due->class_id, 'tab' => 'iuran'])
            ->with('success', 'Iuran berhasil diperbarui.');
    }

    public function destroy(Due $due): RedirectResponse
    {
        $this->assertCanManageClass($due->class_id);
        $classId = $due->class_id;
        $title = $due->title;

        $due->delete();

        return redirect()
            ->route('cash.index', ['class' => $classId, 'tab' => 'iuran'])
            ->with('success', 'Iuran "'.$title.'" beserta riwayat pembayarannya telah dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDue(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'integer', 'min:1000'],
            'period' => ['nullable', 'string', 'max:40'],
            'due_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
