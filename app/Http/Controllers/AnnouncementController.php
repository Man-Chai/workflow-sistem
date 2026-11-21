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
            ->with(['creator', 'classRoom']);

        if (in_array($request->query('priority'), ['normal', 'penting', 'mendesak'], true)) {
            $query->where('priority', $request->query('priority'));
        }

        if ($request->query('class') === 'global') {
            $query->whereNull('class_id');
        } elseif ($request->filled('class')) {
            $query->where('class_id', $request->integer('class'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $query->where(fn (Builder $sub) => $sub->where('title', 'like', '%'.$search.'%')->orWhere('body', 'like', '%'.$search.'%'));
        }

        $announcements = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(8)
            ->withQueryString();

        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        $base = Announcement::query()->visibleTo($user);

        return view('announcements.index', [
            'announcements' => $announcements,
            'classes' => $classes,
            'priority' => $request->query('priority'),
            'classFilter' => $request->query('class'),
            'search' => $request->query('q'),
            'totalCount' => (clone $base)->count(),
            'urgentCount' => (clone $base)->where('priority', 'mendesak')->count(),
            'pinnedCount' => (clone $base)->where('is_pinned', true)->count(),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            abort_unless($announcement->published_at !== null, 404);
            abort_unless(
                $announcement->class_id === null || in_array($announcement->class_id, $user->accessibleClassIds(), true),
                403
            );
        }

        $announcement->load(['creator', 'classRoom']);

        $related = Announcement::query()
            ->visibleTo($user)
            ->with('classRoom')
            ->whereKeyNot($announcement->id)
            ->orderByDesc('published_at')
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
        $classId = $this->managedClassId($request, allowGlobal: true);
        $data = $this->validateAnnouncement($request);

        Announcement::create([
            ...$data,
            'class_id' => $classId,
            'created_by' => $request->user()->id,
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Pemberitahuan berhasil dibuat.');
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
        $classId = $this->managedClassId($request, allowGlobal: true);

        $data = $this->validateAnnouncement($request);
        $publish = $request->boolean('publish');

        $announcement->update([
            ...$data,
            'class_id' => $classId,
            'published_at' => $publish ? ($announcement->published_at ?? now()) : null,
        ]);

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
        if ($announcement->class_id === null) {
            abort_unless(auth()->user()->isAdmin(), 403);
        } else {
            $this->assertCanManageClass($announcement->class_id);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ClassRoom>
     */
    private function availableClasses()
    {
        $user = auth()->user();

        return ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:normal,penting,mendesak'],
            'is_pinned' => ['nullable', 'boolean'],
        ]) + ['is_pinned' => $request->boolean('is_pinned')];
    }
}
