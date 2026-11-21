<div class="feed">
    @forelse($announcements as $announcement)
        <div class="feed-item">
            <span class="feed-icon {{ $announcement->priority === 'mendesak' ? 'is-rose' : ($announcement->priority === 'penting' ? 'is-amber' : '') }}">
                <x-icon :name="$announcement->priority === 'normal' ? 'bell' : 'alert'" :size="15" />
            </span>
            <div class="feed-body">
                <a href="{{ route('announcements.show', $announcement) }}" class="feed-title">{{ $announcement->title }}</a>
                <div class="feed-meta">
                    {{ $announcement->classRoom?->name ?? 'Semua kelas' }}
                    <span>·</span>
                    {{ $announcement->published_at?->diffForHumans() ?? 'Draf' }}
                    @if($announcement->is_pinned)
                        <span class="badge badge-pin">Disematkan</span>
                    @endif
                </div>
            </div>
            <span class="feed-date">{{ $announcement->published_at?->format('d/m') }}</span>
        </div>
    @empty
        <x-empty icon="bell" title="Belum ada pemberitahuan" text="Kabar dari kelas akan muncul di sini." />
    @endforelse
</div>
