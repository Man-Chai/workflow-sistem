<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\Announcement;
use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use ManagesClassData;

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Announcement::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators');

        if ($request->filled('class') && $request->query('class') !== 'global') {
            $query->where('id_kelas', $request->string('class')->toString());
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn (Builder $sub) => $sub->where('agenda', 'ilike', '%'.$search.'%')
                ->orWhere('keterangan', 'ilike', '%'.$search.'%'));
        }

        $announcements = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('mulai')
            ->paginate(8)
            ->withQueryString();

        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        $base = Announcement::query()->visibleTo($user);

        return view('announcements.index', [
            'announcements' => $announcements,
            'classes' => $classes,
            'priority' => $request->query('priority'),
            'classFilter' => $request->query('class'),
            'search' => $request->query('q'),
            'totalCount' => (clone $base)->count(),
            'urgentCount' => (clone $base)->where('tanggal', '>=', today()->toDateString())->count(),
            'pinnedCount' => (clone $base)->where('tanggal', today()->toDateString())->count(),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        $user = auth()->user();

        if (! $user->isAdmin() && $announcement->id_kelas !== null) {
            abort_unless(
                in_array($announcement->id_kelas, $user->accessibleClassIds(), true),
                403
            );
        }

        $announcement->load('classRoom.coordinators');

        $related = Announcement::query()
            ->visibleTo($user)
            ->with('classRoom')
            ->whereKeyNot($announcement->id_agenda)
            ->orderByDesc('tanggal')
            ->take(3)
            ->get();

        return view('announcements.show', [
            'announcement' => $announcement,
            'related' => $related,
        ]);
    }

    public function create(): View
    {
        $classes = $this->availableClasses();

        return view('announcements.form', ['announcement' => null, 'classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classId = $this->managedClassId($request, allowGlobal: false);
        $data = $this->validateAnnouncement($request);

        $announcement = Announcement::create([
            'id_kelas' => $classId,
            'tanggal' => today()->toDateString(),
            'agenda' => $data['title'],
            'mulai' => now()->format('H:i:s'),
            'keterangan' => $data['body'],
        ]);

        \App\Models\Agenda::dispatchN8nReminderWebhook((string) $announcement->id_agenda, 'create');

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Pemberitahuan / agenda berhasil dibuat.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorizeAnnouncement($announcement);

        return view('announcements.form', [
            'announcement' => $announcement,
            'classes' => $this->availableClasses(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncement($announcement);
        $classId = $this->managedClassId($request, allowGlobal: false);
        $data = $this->validateAnnouncement($request);

        $announcement->update([
            'id_kelas' => $classId,
            'agenda' => $data['title'],
            'keterangan' => $data['body'],
        ]);

        \App\Models\Agenda::dispatchN8nReminderWebhook((string) $announcement->id_agenda, 'update');

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Pemberitahuan berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorizeAnnouncement($announcement);
        $announcement->delete();

        return back()->with('success', 'Pemberitahuan telah dihapus.');
    }

    private function authorizeAnnouncement(Announcement $announcement): void
    {
        $this->assertCanManageClass($announcement->id_kelas);
    }

    private function availableClasses()
    {
        $user = auth()->user();

        return ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
    }
}
