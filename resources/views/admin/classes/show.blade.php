@extends('layouts.app')

@section('title', $classRoom->name)

@section('content')
    @php
        $user = auth()->user();
        $canManage = $user->isAdmin() || $user->canManageClass($classRoom->id);
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ $user->isAdmin() ? route('classes.index') : route('dashboard') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali</a>
            <span class="eyebrow"><x-icon name="school" :size="13" /> {{ $classRoom->academic_year }}</span>
            <h1 class="page-title">{{ $classRoom->name }}</h1>
            <p class="page-sub">{{ $classRoom->description ?: 'Kelola siswa, laporan keuangan, dan informasi kelas.' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('cash.index', ['class' => $classRoom->id]) }}" class="btn btn-outline">
                <x-icon name="wallet" :size="16" />
                Buka kas
            </a>
            @if($user->isAdmin())
                <a href="{{ route('classes.edit', $classRoom) }}" class="btn btn-primary">
                    <x-icon name="edit" :size="15" />
                    Ubah kelas
                </a>
            @endif
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Siswa</span>
                <span class="stat-icon"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $classRoom->students->count() }}</div>
            <div class="stat-meta">Terdaftar di kelas ini</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Saldo kas</span>
                <span class="stat-icon is-violet"><x-icon name="wallet" :size="17" /></span>
            </div>
            <div class="stat-value">{{ rupiah($balance) }}</div>
            <div class="stat-meta">Saldo laporan keuangan</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Grup WA</span>
                <span class="stat-icon is-amber"><x-icon name="bell" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $classRoom->grupWa->count() }}</div>
            <div class="stat-meta">{{ $classRoom->grupWa->pluck('nama')->implode(', ') ?: 'Belum terhubung' }}</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Pengurus</span>
                <span class="stat-icon is-blue"><x-icon name="shield" :size="17" /></span>
            </div>
            <div class="stat-value" style="font-size: 17px;">{{ $classRoom->coordinator?->name ?? 'Belum ada' }}</div>
            <div class="stat-meta">{{ $classRoom->coordinator?->email ?? 'Belum ditugaskan' }}</div>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="users" :size="16" /> Daftar siswa</h3>
                    <p class="card-sub">Data siswa (<code>siswa</code>) dan orang tua (<code>ortu</code>) yang terdaftar di kelas ini.</p>
                </div>
                @if($canManage)
                    <a href="{{ route('classes.students.assign', $classRoom) }}" class="btn btn-sm btn-primary">
                        <x-icon name="plus" :size="14" />
                        Tambah siswa
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>ID</th>
                                <th>Orang tua (<code>ortu</code>)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classRoom->students as $student)
                                <tr>
                                    <td>
                                        <div class="cell-user">
                                            <x-avatar :name="$student->name" size="sm" />
                                            <span>
                                                <span class="cell-main">{{ $student->name }}</span>
                                                <span class="cell-sub">{{ $classRoom->name }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="mono">{{ $student->nis }}</td>
                                    <td>
                                        @if($student->ortu)
                                            <span class="cell-main">{{ $student->ortu->name }}</span>
                                            <span class="cell-sub">{{ $student->ortu->email ?: ($student->ortu->phone ?: 'Terdaftar di tabel ortu') }}</span>
                                        @else
                                            <span class="badge badge-warning">Belum terhubung</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('students.edit', ['student' => $student, 'from' => 'class']) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah siswa">
                                                <x-icon name="edit" :size="14" />
                                            </a>
                                            <form method="POST" action="{{ route('students.unassign', $student) }}" data-confirm="Lepaskan {{ $student->name }} dari kelas {{ $classRoom->name }}? Data siswa tetap tersimpan di daftar induk Siswa & Ortu.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Keluarkan dari kelas" title="Keluarkan dari kelas">
                                                    <x-icon name="x" :size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty icon="users" title="Belum ada siswa" text="Tambahkan siswa dari daftar induk ke kelas ini.">
                                            @if($canManage)
                                                <a href="{{ route('classes.students.assign', $classRoom) }}" class="btn btn-sm btn-primary">Tambah siswa</a>
                                            @endif
                                        </x-empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="info" :size="16" /> Informasi kelas</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span>Nama kelas</span>
                            <strong>{{ $classRoom->name }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Jumlah siswa</span>
                            <strong>{{ $classRoom->students->count() }}</strong>
                        </div>
                        <div class="info-item is-full">
                            <span>Pengurus (`kelas_user`)</span>
                            <strong>{{ $classRoom->coordinator?->name ?? 'Belum ditugaskan' }}</strong>
                        </div>
                        <div class="info-item is-full">
                            <span>Email pengurus</span>
                            <strong>{{ $classRoom->coordinator?->email ?? '—' }}</strong>
                        </div>
                    </div>

                    @if($classRoom->description)
                        <div class="notice mt-3">
                            <x-icon name="info" :size="15" />
                            <span>{{ $classRoom->description }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
