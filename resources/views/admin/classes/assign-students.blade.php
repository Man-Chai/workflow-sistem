@extends('layouts.app')

@section('title', 'Tambah Siswa ke '.$classRoom->name)

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('classes.show', $classRoom) }}" class="card-link mb-2">
                <x-icon name="chevron-left" :size="12" /> Kembali ke kelas {{ $classRoom->name }}
            </a>
            <h1 class="page-title">Tambah siswa ke {{ $classRoom->name }}</h1>
            <p class="page-sub">Cari dan centang satu atau beberapa siswa sekaligus dari daftar induk untuk dimasukkan ke kelas ini.</p>
        </div>
        @if($user->isAdmin())
            <div class="page-actions">
                <a href="{{ route('students.create') }}" class="btn btn-outline">
                    <x-icon name="plus" :size="15" />
                    Buat data siswa baru
                </a>
            </div>
        @endif
    </div>

    <div class="card form-card" data-checklist-box>
        <div class="card-body">
            <form method="POST" action="{{ route('students.store', $classRoom) }}">
                @csrf

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div>
                        <h3 class="form-section-title" style="border-bottom: 0; padding-bottom: 0; margin-bottom: 2px;">Daftar siswa tersedia</h3>
                        <span class="hint">Menampilkan siswa yang belum masuk kelas atau berada di kelas lain.</span>
                    </div>
                    <span class="badge badge-brand" data-checklist-counter>0 dipilih</span>
                </div>

                @if($availableStudents->isNotEmpty())
                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 12px;">
                        <div class="search-field" style="flex: 1; min-width: 220px;">
                            <x-icon name="search" :size="15" />
                            <input
                                type="search"
                                class="input"
                                placeholder="Cari nama siswa, nama orang tua, atau status kelas..."
                                autocomplete="off"
                                autofocus
                                data-checklist-search
                            >
                        </div>
                        <button type="button" class="btn btn-sm btn-outline" data-checklist-select-visible>Pilih hasil filter</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-checklist-clear>Reset pilihan</button>
                    </div>

                    <div class="checklist-scroll" style="max-height: 380px;" data-checklist-list>
                        @foreach($availableStudents as $candidate)
                            @php
                                $searchText = strtolower($candidate->name.' '.($candidate->ortu?->name ?? '').' '.($candidate->classRoom?->name ?? 'belum masuk kelas'));
                            @endphp
                            <label class="checklist-row" data-checklist-item data-search-text="{{ $searchText }}">
                                <input
                                    type="checkbox"
                                    name="student_ids[]"
                                    value="{{ $candidate->id_siswa }}"
                                    @checked(is_array(old('student_ids')) && in_array($candidate->id_siswa, old('student_ids'), true))
                                >
                                <x-avatar :name="$candidate->name" size="sm" />
                                <span class="checklist-meta">
                                    <strong>{{ $candidate->name }}</strong>
                                    <small>
                                        @if($candidate->ortu)
                                            Ortu: {{ $candidate->ortu->name }}{{ $candidate->ortu->phone ? ' ('.$candidate->ortu->phone.')' : '' }}
                                        @else
                                            Ortu belum terhubung
                                        @endif
                                    </small>
                                </span>
                                <span class="checklist-badge">
                                    @if($candidate->classRoom)
                                        <span class="badge badge-neutral">Di {{ $candidate->classRoom->name }}</span>
                                    @else
                                        <span class="badge badge-lime">Belum punya kelas</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                        <div class="sselect-empty" data-checklist-empty style="display: none;">
                            Tidak ada nama siswa yang cocok dengan pencarian.
                        </div>
                    </div>
                @else
                    <x-empty icon="users" title="Tidak ada siswa tersedia" text="Semua siswa yang terdaftar sudah berada di kelas ini, atau belum ada data siswa di daftar induk.">
                        @if($user->isAdmin())
                            <a href="{{ route('students.create') }}" class="btn btn-sm btn-primary">Tambah siswa ke daftar induk</a>
                        @endif
                    </x-empty>
                @endif

                @error('student_ids') <span class="error mt-1" style="display:block;">{{ $message }}</span> @enderror
                @error('student_ids.*') <span class="error mt-1" style="display:block;">{{ $message }}</span> @enderror

                <div class="form-actions">
                    <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-ghost">Batal</a>
                    @if($availableStudents->isNotEmpty())
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" :size="15" />
                            Tambahkan ke {{ $classRoom->name }}
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
