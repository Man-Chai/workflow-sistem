@extends('layouts.app')

@section('title', 'Siswa & Orang Tua')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="users" :size="13" /> Data induk akademik</span>
            <h1 class="page-title">Siswa & orang tua</h1>
            <p class="page-sub">Kelola daftar induk siswa (<code>siswa</code>) dan orang tua (<code>ortu</code>). Siswa yang terdaftar di sini dapat dipilih melalui checklist pencarian pada halaman kelas.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('parents.create') }}" class="btn btn-outline">
                <x-icon name="plus" :size="16" />
                Tambah orang tua
            </a>
            <a href="{{ route('students.create') }}" class="btn btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah siswa
            </a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total siswa</span>
                <span class="stat-icon"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalStudents }}</div>
            <div class="stat-meta">Terdaftar di tabel <code>siswa</code></div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Sudah masuk kelas</span>
                <span class="stat-icon is-blue"><x-icon name="school" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $assignedStudents }}</div>
            <div class="stat-meta">Memiliki kelas aktif</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Belum punya kelas</span>
                <span class="stat-icon is-amber"><x-icon name="alert" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $unassignedStudents }}</div>
            <div class="stat-meta">Siap ditempatkan ke kelas</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Data orang tua</span>
                <span class="stat-icon is-violet"><x-icon name="user" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalParents }}</div>
            <div class="stat-meta">Terdaftar di tabel <code>ortu</code></div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="tabs">
            <a href="{{ route('students.index', ['tab' => 'siswa']) }}" class="tab {{ $tab === 'siswa' && ! $filter ? 'is-active' : '' }}">
                Daftar Siswa <span class="tab-count">{{ $totalStudents }}</span>
            </a>
            <a href="{{ route('students.index', ['tab' => 'siswa', 'filter' => 'unassigned']) }}" class="tab {{ $tab === 'siswa' && $filter === 'unassigned' ? 'is-active' : '' }}">
                Belum Masuk Kelas <span class="tab-count">{{ $unassignedStudents }}</span>
            </a>
            <a href="{{ route('students.index', ['tab' => 'ortu']) }}" class="tab {{ $tab === 'ortu' ? 'is-active' : '' }}">
                Daftar Orang Tua (<code>ortu</code>) <span class="tab-count">{{ $totalParents }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('students.index') }}" class="filter-right">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if($filter)
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <div class="search-field">
                <x-icon name="search" :size="15" />
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="{{ $tab === 'ortu' ? 'Cari nama ortu, telepon, email...' : 'Cari nama siswa, ortu, atau kelas...' }}">
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Cari</button>
            @if($search)
                <a href="{{ route('students.index', array_filter(['tab' => $tab, 'filter' => $filter])) }}" class="btn btn-sm btn-ghost">Reset</a>
            @endif
        </form>
    </div>

    @if($tab === 'siswa')
        <div class="card">
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Kode / ID</th>
                                <th>Orang Tua (<code>ortu</code>)</th>
                                <th>Kelas</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>
                                        <div class="cell-user">
                                            <x-avatar :name="$student->name" size="sm" />
                                            <span>
                                                <span class="cell-main">{{ $student->name }}</span>
                                                <span class="cell-sub tiny muted">UUID: {{ $student->id_siswa }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="mono">{{ $student->nis }}</td>
                                    <td>
                                        @if($student->ortu)
                                            <span class="cell-main">{{ $student->ortu->name }}</span>
                                            <span class="cell-sub">
                                                {{ $student->ortu->phone ?: '' }}
                                                @if($student->ortu->phone && $student->ortu->email) · @endif
                                                {{ $student->ortu->email ?: '' }}
                                                @if(! $student->ortu->phone && ! $student->ortu->email) Terdaftar di tabel ortu @endif
                                            </span>
                                        @else
                                            <span class="badge badge-warning">Belum dihubungkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->classRoom)
                                            <a href="{{ route('classes.show', $student->classRoom) }}" class="badge badge-brand" style="text-decoration: none;">
                                                <x-icon name="school" :size="12" />
                                                {{ $student->classRoom->name }}
                                            </a>
                                        @else
                                            <span class="badge badge-neutral">Belum masuk kelas</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('students.edit', $student) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah {{ $student->name }}">
                                                <x-icon name="edit" :size="14" />
                                            </a>
                                            <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Hapus data siswa {{ $student->name }} secara permanen?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus siswa">
                                                    <x-icon name="trash" :size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty icon="users" title="Belum ada data siswa" text="Tambahkan data siswa baru ke daftar induk.">
                                            <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary">Tambah siswa</a>
                                        </x-empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $students->links() }}
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Orang Tua</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Anak Terhubung</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parents as $parent)
                                <tr>
                                    <td>
                                        <div class="cell-user">
                                            <x-avatar :name="$parent->name" size="sm" />
                                            <span>
                                                <span class="cell-main">{{ $parent->name }}</span>
                                                <span class="cell-sub tiny muted">UUID: {{ $parent->id_ortu }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $parent->phone ?: '—' }}</td>
                                    <td>{{ $parent->email ?: '—' }}</td>
                                    <td>
                                        @if($parent->students->isNotEmpty())
                                            <span class="badge badge-info">
                                                {{ $parent->students->pluck('name')->implode(', ') }}
                                            </span>
                                        @else
                                            <span class="tiny muted">Belum ada siswa terhubung</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('parents.edit', $parent) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah {{ $parent->name }}">
                                                <x-icon name="edit" :size="14" />
                                            </a>
                                            <form method="POST" action="{{ route('parents.destroy', $parent) }}" data-confirm="Hapus data orang tua {{ $parent->name }}? Siswa yang terhubung akan dilepas tautan orang tuanya.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus orang tua">
                                                    <x-icon name="trash" :size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty icon="user" title="Belum ada data orang tua" text="Tambahkan data orang tua baru ke daftar induk.">
                                            <a href="{{ route('parents.create') }}" class="btn btn-sm btn-primary">Tambah orang tua</a>
                                        </x-empty>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $parents->links() }}
            </div>
        </div>
    @endif
@endsection
