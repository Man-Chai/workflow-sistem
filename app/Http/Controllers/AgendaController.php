<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\Agenda;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AgendaController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = in_array($request->query('status'), ['upcoming', 'past', 'all'], true)
            ? $request->query('status')
            : 'all';

        $query = Agenda::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators');

        if ($request->filled('class') && $request->query('class') !== 'global') {
            $query->where('id_kelas', $request->string('class')->toString());
        }

        $query = match ($status) {
            'past' => $query->where('tanggal', '<', today()->toDateString())->orderByDesc('tanggal')->orderByDesc('mulai'),
            'upcoming' => $query->where('tanggal', '>=', today()->toDateString())->orderBy('tanggal')->orderBy('mulai'),
            default => $query->orderByDesc('tanggal')->orderByDesc('mulai'),
        };

        $agendas = $query->paginate(9)->withQueryString();

        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        return view('agenda.index', [
            'agendas' => $agendas,
            'classes' => $classes,
            'status' => $status,
            'classFilter' => $request->query('class'),
            'upcomingCount' => Agenda::query()->visibleTo($user)->where('tanggal', '>=', today()->toDateString())->count(),
            'monthCount' => Agenda::query()
                ->visibleTo($user)
                ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
            'schoolCount' => Agenda::query()->visibleTo($user)->count(),
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        abort_if($classes->isEmpty() && ! $user->isAdmin(), 403, 'Anda belum ditugaskan ke kelas.');

        return view('agenda.form', ['agenda' => null, 'classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classId = $this->managedClassId($request, allowGlobal: false);
        $data = $this->validateAgenda($request);

        $starts = Carbon::parse($data['starts_at']);
        $ends = ! empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;

        $agenda = Agenda::create([
            'id_kelas' => $classId,
            'tanggal' => $starts->toDateString(),
            'agenda' => $data['title'],
            'mulai' => $starts->format('H:i:s'),
            'selesai' => $ends?->format('H:i:s'),
            'keterangan' => $data['description'] ?? null,
        ]);

        Agenda::dispatchN8nReminderWebhook((string) $agenda->id_agenda, 'create');

        return redirect()
            ->route('agenda.index')
            ->with('success', 'Agenda berhasil ditambahkan.');
    }

    public function edit(Agenda $agenda): View
    {
        $this->authorizeAgenda($agenda);

        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        return view('agenda.form', ['agenda' => $agenda, 'classes' => $classes]);
    }

    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        $this->authorizeAgenda($agenda);
        $classId = $this->managedClassId($request, allowGlobal: false);
        $data = $this->validateAgenda($request);

        $starts = Carbon::parse($data['starts_at']);
        $ends = ! empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : null;

        $agenda->update([
            'id_kelas' => $classId,
            'tanggal' => $starts->toDateString(),
            'agenda' => $data['title'],
            'mulai' => $starts->format('H:i:s'),
            'selesai' => $ends?->format('H:i:s'),
            'keterangan' => $data['description'] ?? null,
        ]);

        Agenda::dispatchN8nReminderWebhook((string) $agenda->id_agenda, 'update');

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
        $this->assertCanManageClass($agenda->id_kelas);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAgenda(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);
    }
}
