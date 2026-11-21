@extends('layouts.app')

@section('title', 'Pemberitahuan')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="bell" :size="13" /> Kabar komunitas</span>
            <h1 class="page-title">Pemberitahuan</h1>
            <p class="page-sub">Informasi resmi dari sekolah dan koordinator kelas, tersusun rapi berdasarkan prioritas.</p>
        </div>
        <div class="page-actions">
            @if($user->isAdmin() || $user->isCoordinator())
                <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                    <x-icon name="plus" :size="16" />
                    Buat pemberitahuan
                </a>
            @endif
        </div>
    </div>

    <div class="stat-grid stat-grid-3">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total pemberitahuan</span>
                <span class="stat-icon"><x-icon name="bell" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalCount }}</div>
            <div class="stat-meta">Terlihat untuk akun Anda</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Mendesak</span>
                <span class="stat-icon is-rose"><x-icon name="alert" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $urgentCount }}</div>
            <div class="stat-meta">Perlu perhatian segera</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Disematkan</span>
                <span class="stat-icon is-amber"><x-icon name="pin" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $pinnedCount }}</div>
            <div class="stat-meta">Selalu tampil di atas</div>
        </div>
    </div>

    <form method="GET" action="{{ route('announcements.index') }}" class="filter-bar">
        <div class="filter-left">
            <div class="search-field">
                <x-icon name="search" :size="15" />
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Cari judul atau isi kabar...">
            </div>
            <select name="priority" class="select" aria-label="Filter prioritas">
                <option value="">Semua prioritas</option>
                <option value="mendesak" @selected($priority === 'mendesak')>Mendesak</option>
                <option value="penting" @selected($priority === 'penting')>Penting</option>
                <option value="normal" @selected($priority === 'normal')>Informasi</option>
            </select>
            @if($classes->isNotEmpty())
                <select name="class" class="select" aria-label="Filter kelas">
                    <option value="">Semua kelas saya</option>
                    @if($user->isAdmin())
                        <option value="global" @selected($classFilter === 'global')>Pemberitahuan sekolah</option>
                    @endif
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) $classFilter === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div class="filter-right">
            <button type="submit" class="btn btn-sm btn-outline">Terapkan</button>
            @if($search || $priority || $classFilter)
                <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    @if($announcements->isEmpty())
        <div class="card">
            <div class="card-body">
                <x-empty icon="bell" title="Belum ada pemberitahuan" text="Kabar dari sekolah dan koordinator kelas akan tampil di sini.">
                    @if($user->isAdmin() || $user->isCoordinator())
                        <a href="{{ route('announcements.create') }}" class="btn btn-sm btn-primary">Buat pemberitahuan</a>
                    @endif
                </x-empty>
            </div>
        </div>
    @else
        <div class="stack" style="gap: 11px;">
            @foreach($announcements as $announcement)
                @php
                    $canManage = $user->isAdmin() || ($announcement->class_id && $user->canManageClass($announcement->class_id));
                @endphp
                <article class="announcement-card priority-{{ $announcement->priority }}">
                    <div class="announcement-top">
                        <div class="announcement-badges">
                            <span class="badge {{ $announcement->priority === 'mendesak' ? 'badge-danger' : ($announcement->priority === 'penting' ? 'badge-warning' : 'badge-info') }}">
                                {{ $announcement->priorityLabel() }}
                            </span>
                            @if($announcement->is_pinned)
                                <span class="badge badge-pin"><x-icon name="pin" :size="10" /> Disematkan</span>
                            @endif
                            <span class="badge badge-neutral">{{ $announcement->classRoom?->name ?? 'Semua kelas' }}</span>
                        </div>
                        <span class="tiny muted nowrap">{{ $announcement->published_at?->diffForHumans() ?? 'Draf' }}</span>
                    </div>

                    <h2 class="announcement-title">
                        <a href="{{ route('announcements.show', $announcement) }}">{{ $announcement->title }}</a>
                    </h2>
                    <p class="announcement-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 210) }}</p>

                    <div class="announcement-foot">
                        <div class="cell-user">
                            <x-avatar :name="$announcement->creator?->name ?? 'Sistem'" size="sm" />
                            <span>
                                <span class="cell-main">{{ $announcement->creator?->name ?? 'Sistem' }}</span>
                                <span class="cell-sub">{{ $announcement->published_at?->translatedFormat('d F Y · H:i') ?? 'Belum diterbitkan' }}</span>
                            </span>
                        </div>
                        <div class="row-actions">
                            <a href="{{ route('announcements.show', $announcement) }}" class="btn btn-sm btn-outline">Baca</a>
                            @if($canManage)
                                <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-ghost">
                                    <x-icon name="edit" :size="13" /> Ubah
                                </a>
                                <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" data-confirm="Hapus pemberitahuan ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost" aria-label="Hapus pemberitahuan">
                                        <x-icon name="trash" :size="13" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $announcements->links() }}
    @endif
@endsection
