<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\GrupWa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrupWaController extends Controller
{
    public function index(Request $request): View
    {
        $query = GrupWa::query()->with('classRoom');

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn ($sub) => $sub->where('nama', 'ilike', '%'.$search.'%')
                ->orWhere('id_grup', 'ilike', '%'.$search.'%')
                ->orWhereHas('classRoom', fn ($c) => $c->where('nama', 'ilike', '%'.$search.'%')));
        }

        if ($request->query('filter') === 'unlinked') {
            $query->whereNull('id_kelas');
        } elseif ($request->query('filter') === 'linked') {
            $query->whereNotNull('id_kelas');
        }

        $groups = $query->orderBy('nama')->paginate(12)->withQueryString();

        return view('admin.wa-groups.index', [
            'groups' => $groups,
            'search' => $request->query('q'),
            'filter' => $request->query('filter'),
            'totalGroups' => GrupWa::query()->count(),
            'linkedGroups' => GrupWa::query()->whereNotNull('id_kelas')->count(),
            'unlinkedGroups' => GrupWa::query()->whereNull('id_kelas')->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.wa-groups.form', [
            'group' => null,
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateGroup($request);

        GrupWa::create([
            'nama' => $data['name'],
            'id_grup' => $data['group_id'],
            'id_kelas' => ! empty($data['class_id']) ? $data['class_id'] : null,
        ]);

        return redirect()
            ->route('wa-groups.index')
            ->with('success', 'Grup WhatsApp '.$data['name'].' berhasil ditambahkan.');
    }

    public function edit(GrupWa $waGroup): View
    {
        $waGroup->load('classRoom');

        return view('admin.wa-groups.form', [
            'group' => $waGroup,
            'classes' => ClassRoom::query()->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, GrupWa $waGroup): RedirectResponse
    {
        $data = $this->validateGroup($request, $waGroup);

        $waGroup->update([
            'nama' => $data['name'],
            'id_grup' => $data['group_id'],
            'id_kelas' => ! empty($data['class_id']) ? $data['class_id'] : null,
        ]);

        return redirect()
            ->route('wa-groups.index')
            ->with('success', 'Grup WhatsApp '.$waGroup->name.' berhasil diperbarui.');
    }

    public function destroy(GrupWa $waGroup): RedirectResponse
    {
        $name = $waGroup->name;
        $waGroup->delete();

        return redirect()
            ->route('wa-groups.index')
            ->with('success', 'Grup WhatsApp '.$name.' telah dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGroup(Request $request, ?GrupWa $waGroup = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'group_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('grup_wa', 'id_grup')->ignore($waGroup?->id_grup_wa, 'id_grup_wa'),
            ],
            'class_id' => [
                'nullable',
                Rule::exists('kelas', 'id_kelas'),
            ],
        ], [
            'group_id.unique' => 'ID Grup WhatsApp ini sudah terdaftar.',
        ]);
    }
}
