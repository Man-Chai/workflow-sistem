<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\Agenda;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = in_array($request->query('status'), ['upcoming', 'past', 'all'], true)
            ? $request->query('status')
            : 'upcoming';

        $query = Agenda::query()
            ->visibleTo($user)
            ->with(['classRoom', 'creator']);

        if ($request->query('class') === 'global') {
            $query->whereNull('class_id');
        } elseif ($request->filled('class')) {
            $query->where('class_id', $request->integer('class'));
        }

        $query = match ($status) {
            'past' => $query->where('starts_at', '<', now())->orderByDesc('starts_at'),
            'all' => $query->orderByDesc('starts_at'),
            default => $query->where('starts_at', '>=', now())->orderBy('starts_at'),
        };

        $agendas = $query->paginate(9)->withQueryString();

        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        return view('agenda.index', [
            'agendas' => $agendas,
            'classes' => $classes,
            'status' => $status,
            'classFilter' => $request->query('class'),
            'upcomingCount' => Agenda::query()->visibleTo($user)->where('starts_at', '>=', now())->count(),
            'monthCount' => Agenda::query()
                ->visibleTo($user)
                ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'schoolCount' => Agenda::query()
                ->visibleTo($user)
                ->whereNull('class_id')
                ->count(),
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        abort_if($classes->isEmpty() && ! $user->isAdmin(), 403, 'Anda belum ditugaskan ke kelas.');

        return view('agenda.form', ['agenda' => null, 'classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classId = $this->managedClassId($request, allowGlobal: true);
        $data = $this->validateAgenda($request);

        Agenda::create([
            ...$data,
            'class_id' => $classId,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function edit(Agenda $agenda): View
    {
        $this->authorizeAgenda($agenda);

        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        return view('agenda.form', ['agenda' => $agenda, 'classes' => $classes]);
    }

    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->authorizeAgenda($agenda);
        $classId = $this->managedClassId($request, allowGlobal: true);

        $agenda->update([
            ...$this->validateAgenda($request),
            'class_id' => $classId,
        ]);

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Agenda berhasil diperbarui.');
    }

    public function destroy(Agenda $agenda): RedirectResponse
    {
        $this->authorizeAgenda($agenda);
        $agenda->delete();

        return back()->with('success', 'Agenda telah dihapus.');
    }

    private function authorizeAgenda(Agenda $agenda): void
    {
        if ($agenda->class_id === null) {
            abort_unless(auth()->user()->isAdmin(), 403);
        } else {
            $this->assertCanManageClass($agenda->class_id);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAgenda(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:140'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_published' => ['nullable', 'boolean'],
        ]) + ['is_published' => $request->boolean('is_published')];
    }
}
