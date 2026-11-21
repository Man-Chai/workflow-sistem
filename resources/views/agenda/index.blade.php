@extends('layouts.app')

@section('title', 'Agenda')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="calendar" :size="13" /> Kalender komunitas</span>
            <h1 class="page-title">Agenda kelas</h1>
            <p class="page-sub">Rapat, kegiatan, dan momen penting kelas dalam satu kalender bersama.</p>
        </div>
        <div class="page-actions">
            @if($user->isAdmin() || $user->isCoordinator())
                <a href="{{ route('agenda.create') }}" class="btn btn-primary">
                    <x-icon name="plus" :size="16" />
                    Tambah agenda
                </a>
            @endif
        </div>
    </div>

    <div class="stat-grid stat-grid-3">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Agenda mendatang</span>
                <span class="stat-icon"><x-icon name="calendar" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $upcomingCount }}</div>
            <div class="stat-meta">Kegiatan yang belum berlangsung</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Bulan ini</span>
                <span class="stat-icon is-blue"><x-icon name="clock" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $monthCount }}</div>
            <div class="stat-meta">{{ now()->translatedFormat('F Y') }}</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Agenda sekolah</span>
                <span class="stat-icon is-violet"><x-icon name="school" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $schoolCount }}</div>
            <div class="stat-meta">Berlaku untuk seluruh kelas</div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="tabs">
            <a href="{{ route('agenda.index', ['status' => 'upcoming', 'class' => $classFilter]) }}" class="tab {{ $status === 'upcoming' ? 'is-active' : '' }}">Mendatang</a>
            <a href="{{ route('agenda.index', ['status' => 'past', 'class' => $classFilter]) }}" class="tab {{ $status === 'past' ? 'is-active' : '' }}">Sudah lewat</a>
            <a href="{{ route('agenda.index', ['status' => 'all', 'class' => $classFilter]) }}" class="tab {{ $status === 'all' ? 'is-active' : '' }}">Semua</a>
        </div>

        @if($user->isAdmin() || $classes->count() > 1)
            <form method="GET" action="{{ route('agenda.index') }}" class="filter-right">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="class" class="select" data-auto-submit aria-label="Filter kelas">
                    <option value="">Semua kelas saya</option>
                    @if($user->isAdmin())
                        <option value="global" @selected($classFilter === 'global')>Agenda sekolah (global)</option>
                    @endif
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((string) $classFilter === (string) $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if($agendas->isEmpty())
        <div class="card">
            <div class="card-body">
                <x-empty icon="calendar" title="Belum ada agenda" text="Agenda yang sesuai filter akan tampil di sini. Tambahkan agenda agar seluruh orang tua mendapat informasi kegiatan.">
                    @if($user->isAdmin() || $user->isCoordinator())
                        <a href="{{ route('agenda.create') }}" class="btn btn-sm btn-primary">Tambah agenda</a>
                    @endif
                </x-empty>
            </div>
        </div>
    @else
        <div class="agenda-grid">
            @foreach($agendas as $agenda)
                @php
                    $canManage = $user->isAdmin() || ($agenda->class_id && $user->canManageClass($agenda->class_id));
                    $isPast = $agenda->starts_at->isPast();
                @endphp
                <article class="agenda-card">
                    <div class="agenda-card-head">
                        <div class="date-block" style="width: 50px;">
                            <strong style="font-size: 17px;">{{ $agenda->starts_at->format('d') }}</strong>
                            <span>{{ $agenda->starts_at->translatedFormat('M') }}</span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h3>{{ $agenda->title }}</h3>
                            <div class="agenda-meta">
                                <span class="badge {{ $isPast ? 'badge-neutral' : 'badge-brand' }}">
                                    {{ $isPast ? 'Sudah berlangsung' : 'Akan datang' }}
                                </span>
                                <span class="badge badge-neutral">{{ $agenda->classRoom?->name ?? 'Semua kelas' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="agenda-meta">
                        <span><x-icon name="clock" :size="11" /> {{ $agenda->starts_at->translatedFormat('l, d M Y · H:i') }} WIB</span>
                        @if($agenda->location)
                            <span><x-icon name="pin" :size="11" /> {{ $agenda->location }}</span>
                        @endif
                    </div>

                    @if($agenda->description)
                        <p class="agenda-desc clamp-3">{{ $agenda->description }}</p>
                    @endif

                    <div class="agenda-actions">
                        <span class="tiny muted">oleh {{ $agenda->creator?->name ?? 'Sistem' }}</span>
                        @if($canManage)
                            <div class="row-actions" style="margin-left: auto;">
                                <a href="{{ route('agenda.edit', $agenda) }}" class="btn btn-sm btn-outline">
                                    <x-icon name="edit" :size="13" /> Ubah
                                </a>
                                <form method="POST" action="{{ route('agenda.destroy', $agenda) }}" data-confirm="Hapus agenda ini?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-ghost" aria-label="Hapus agenda">
                                        <x-icon name="trash" :size="13" />
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        {{ $agendas->links() }}
    @endif
@endsection
