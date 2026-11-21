@extends('layouts.app')

@section('title', 'Kelas & Siswa')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="school" :size="13" /> Struktur kelas</span>
            <h1 class="page-title">Kelas & siswa</h1>
            <p class="page-sub">Atur kelas, tugaskan koordinator, dan kelola data siswa dalam satu tempat.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('classes.create') }}" class="btn btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah kelas
            </a>
        </div>
    </div>

    <div class="stat-grid stat-grid-3">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total kelas</span>
                <span class="stat-icon"><x-icon name="school" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalClasses }}</div>
            <div class="stat-meta">Tahun ajaran aktif</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total siswa</span>
                <span class="stat-icon is-blue"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalStudents }}</div>
            <div class="stat-meta">Tersebar di seluruh kelas</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Belum ada koordinator</span>
                <span class="stat-icon is-amber"><x-icon name="alert" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $unassigned }}</div>
            <div class="stat-meta">Kelas yang perlu ditugaskan</div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" action="{{ route('classes.index') }}" class="filter-left">
            <div class="search-field">
                <x-icon name="search" :size="15" />
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Cari kelas atau tahun ajaran...">
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Cari</button>
            @if($search)
                <a href="{{ route('classes.index') }}" class="btn btn-sm btn-ghost">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kelas</th>
                            <th>Koordinator</th>
                            <th>Siswa</th>
                            <th>Saldo kas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <span class="stat-icon" style="width: 33px; height: 33px; border-radius: 10px;">
                                            <x-icon name="school" :size="16" />
                                        </span>
                                        <span>
                                            <a href="{{ route('classes.show', $row['class']) }}" class="cell-main" style="text-decoration: none;">{{ $row['class']->name }}</a>
                                            <span class="cell-sub">Tahun ajaran {{ $row['class']->academic_year }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($row['class']->coordinator)
                                        <div class="cell-user">
                                            <x-avatar :name="$row['class']->coordinator->name" size="sm" />
                                            <span>
                                                <span class="cell-main">{{ $row['class']->coordinator->name }}</span>
                                                <span class="cell-sub">{{ $row['class']->coordinator->email }}</span>
                                            </span>
                                        </div>
                                    @else
                                        <span class="badge badge-warning">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td><span class="amount">{{ $row['class']->students_count }}</span> <span class="tiny muted">siswa</span></td>
                                <td><span class="amount">{{ rupiah($row['balance']) }}</span></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('classes.show', $row['class']) }}" class="btn btn-sm btn-outline">Detail</a>
                                        <a href="{{ route('classes.edit', $row['class']) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah kelas">
                                            <x-icon name="edit" :size="14" />
                                        </a>
                                        <form method="POST" action="{{ route('classes.destroy', $row['class']) }}" data-confirm="Hapus kelas {{ $row['class']->name }} beserta siswa, kas, iuran, agenda, dan pemberitahuannya?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus kelas">
                                                <x-icon name="trash" :size="14" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty icon="school" title="Belum ada kelas" text="Tambahkan kelas pertama untuk mulai mengelola siswa, kas, dan agenda.">
                                        <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">Tambah kelas</a>
                                    </x-empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $classes->links() }}
        </div>
    </div>
@endsection
