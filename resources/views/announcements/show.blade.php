@extends('layouts.app')

@section('title', 'Detail Pemberitahuan')

@section('content')
    @php
        $user = auth()->user();
        $canManage = $user->isAdmin() || ($announcement->class_id && $user->canManageClass($announcement->class_id));
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('announcements.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke pemberitahuan</a>
            <h1 class="page-title">Detail pemberitahuan</h1>
            <p class="page-sub">Kabar resmi dari {{ $announcement->classRoom?->name ?? 'sekolah' }}.</p>
        </div>
    </div>

    <div class="grid grid-main">
        <article class="card">
            <div class="card-body">
                <div class="announcement-badges mb-2">
                    <span class="badge {{ $announcement->priority === 'mendesak' ? 'badge-danger' : ($announcement->priority === 'penting' ? 'badge-warning' : 'badge-info') }}">
                        {{ $announcement->priorityLabel() }}
                    </span>
                    @if($announcement->is_pinned)
                        <span class="badge badge-pin"><x-icon name="pin" :size="10" /> Disematkan</span>
                    @endif
                    <span class="badge badge-neutral">{{ $announcement->classRoom?->name ?? 'Semua kelas' }}</span>
                    @if(! $announcement->published_at)
                        <span class="badge badge-warning">Draf</span>
                    @endif
                </div>

                <h1 class="page-title" style="font-size: 25px; margin-bottom: 12px;">{{ $announcement->title }}</h1>

                <div class="article-meta">
                    <span><x-icon name="user" :size="13" /> {{ $announcement->creator?->name ?? 'Sistem' }}</span>
                    <span><x-icon name="calendar" :size="13" /> {{ $announcement->published_at?->translatedFormat('l, d F Y · H:i') ?? 'Belum diterbitkan' }}</span>
                    <span><x-icon name="school" :size="13" /> {{ $announcement->classRoom?->name ?? 'Seluruh sekolah' }}</span>
                </div>

                <div class="article-body">
                    {!! nl2br(e($announcement->body)) !!}
                </div>

                <div class="article-actions">
                    @if($canManage)
                        <a href="{{ route('announcements.edit', $announcement) }}" class="btn btn-sm btn-outline">
                            <x-icon name="edit" :size="13" /> Ubah pemberitahuan
                        </a>
                        <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" data-confirm="Hapus pemberitahuan ini?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <x-icon name="trash" :size="13" /> Hapus
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-ghost" style="margin-left: auto;">Tutup</a>
                </div>
            </div>
        </article>

        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="info" :size="16" /> Informasi</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span>Prioritas</span>
                            <strong>{{ $announcement->priorityLabel() }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Status</span>
                            <strong>{{ $announcement->published_at ? 'Terbit' : 'Draf' }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Cakupan</span>
                            <strong>{{ $announcement->classRoom?->name ?? 'Semua kelas' }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Disematkan</span>
                            <strong>{{ $announcement->is_pinned ? 'Ya' : 'Tidak' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="bell" :size="16" /> Kabar lainnya</h3>
                        <p class="card-sub">Pemberitahuan terbaru untuk Anda.</p>
                    </div>
                </div>
                <div class="card-body">
                    @include('partials.announcement-feed', ['announcements' => $related])
                </div>
            </div>
        </div>
    </div>
@endsection
