@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="users" :size="13" /> Ruang orang tua</span>
            <h1 class="page-title">{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="page-sub">Pantau agenda dan kabar kelas anak Anda. {{ now()->translatedFormat('l, d F Y') }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('agenda.index') }}" class="btn btn-outline">
                <x-icon name="calendar" :size="16" />
                Lihat agenda
            </a>
            <a href="{{ route('announcements.index') }}" class="btn btn-primary">
                <x-icon name="bell" :size="16" />
                Pemberitahuan
            </a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Anak terdaftar</span>
                <span class="stat-icon"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $children->count() }}</div>
            <div class="stat-meta"><x-icon name="school" :size="12" /> {{ $classCount }} kelas terhubung</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Agenda mendatang</span>
                <span class="stat-icon is-blue"><x-icon name="calendar" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $upcomingAgendas->count() }}</div>
            <div class="stat-meta"><x-icon name="clock" :size="12" /> Kegiatan terdekat</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Kabar terbaru</span>
                <span class="stat-icon is-violet"><x-icon name="bell" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $announcements->count() }}</div>
            <div class="stat-meta">Pemberitahuan untuk kelas Anda</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Pengelola kas</span>
                <span class="stat-icon is-amber"><x-icon name="shield" :size="17" /></span>
            </div>
            <div class="stat-value" style="font-size: 18px;">Koordinator</div>
            <div class="stat-meta">Kas dan iuran dikelola koordinator kelas</div>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="users" :size="16" /> Anak saya</h3>
                    <p class="card-sub">Data siswa yang terhubung dengan akun Anda.</p>
                </div>
            </div>
            <div class="card-body">
                @forelse($children as $child)
                    <div class="child-card">
                        <x-avatar :name="$child->name" size="lg" />
                        <div class="child-meta">
                            <strong>{{ $child->name }}</strong>
                            <span>{{ $child->classRoom?->name }} · NIS {{ $child->nis }}</span>
                            <span>{{ $child->classRoom?->academic_year }}</span>
                        </div>
                        <span class="badge badge-brand">{{ $child->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</span>
                    </div>
                @empty
                    <x-empty icon="users" title="Belum ada data anak" text="Hubungi administrator atau koordinator untuk menghubungkan akun Anda dengan data siswa." />
                @endforelse
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="wallet" :size="16" /> Kas kelas</h3>
                        <p class="card-sub">Transparansi tanpa akses transaksi.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="notice">
                        <x-icon name="info" :size="15" />
                        <span>Kas, iuran, dan pencatatan pembayaran dikelola oleh <strong>koordinator kelas</strong>. Orang tua menerima informasi agenda dan pemberitahuan resmi dari kelas.</span>
                    </div>
                    <div class="divider"></div>
                    <div class="progress-meta">
                        <span>Pertanyaan tentang iuran?</span>
                        <strong>Hubungi koordinator kelas</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="bell" :size="16" /> Kabar terbaru</h3>
                        <p class="card-sub">Informasi terakhir untuk kelas Anda.</p>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="card-link">Semua kabar</a>
                </div>
                <div class="card-body">
                    @if($latestAnnouncement)
                        <div class="feed">
                            <div class="feed-item">
                                <span class="feed-icon"><x-icon name="bell" :size="15" /></span>
                                <div class="feed-body">
                                    <a href="{{ route('announcements.show', $latestAnnouncement) }}" class="feed-title">{{ $latestAnnouncement->title }}</a>
                                    <div class="feed-meta">{{ $latestAnnouncement->classRoom?->name ?? 'Seluruh sekolah' }} · {{ $latestAnnouncement->published_at?->format('d M Y') }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <x-empty icon="bell" title="Belum ada kabar" text="Pemberitahuan dari koordinator akan tampil di sini." />
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="calendar" :size="16" /> Agenda mendatang</h3>
                    <p class="card-sub">Kegiatan kelas dan sekolah yang perlu diketahui.</p>
                </div>
                <a href="{{ route('agenda.index') }}" class="card-link">Lihat agenda</a>
            </div>
            <div class="card-body">
                @include('partials.agenda-list', ['agendas' => $upcomingAgendas])
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="bell" :size="16" /> Pemberitahuan terbaru</h3>
                    <p class="card-sub">Kabar penting dari kelas dan sekolah.</p>
                </div>
                <a href="{{ route('announcements.index') }}" class="card-link">Semua kabar</a>
            </div>
            <div class="card-body">
                @include('partials.announcement-feed', ['announcements' => $announcements])
            </div>
        </div>
    </div>
@endsection
