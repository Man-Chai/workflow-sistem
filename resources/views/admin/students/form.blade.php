@extends('layouts.app')

@section('title', $student ? 'Ubah Siswa' : 'Tambah Siswa')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ request()->query('from') === 'class' && $student?->class_id ? route('classes.show', $student->class_id) : route('students.index') }}" class="card-link mb-2">
                <x-icon name="chevron-left" :size="12" /> Kembali ke daftar siswa
            </a>
            <h1 class="page-title">{{ $student ? 'Ubah data siswa' : 'Tambah siswa' }}</h1>
            <p class="page-sub">{{ $student ? 'Perbarui nama, orang tua, atau penempatan kelas siswa.' : 'Tambahkan siswa baru ke daftar induk siswa dan hubungkan dengan data orang tua.' }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $student ? route('students.update', ['student' => $student, 'from' => request()->query('from')]) : route('students.store-master') }}">
                @csrf
                @if($student)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="name">Nama lengkap siswa <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $student?->name) }}" required maxlength="100" placeholder="Contoh: Budi Santoso">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="parent_id">Orang tua terdaftar (tabel <code>ortu</code>)</label>
                        <select name="parent_id" id="parent_id" class="select" data-searchable-select data-placeholder="Ketik nama, telepon, atau email orang tua...">
                            <option value="">— {{ $student ? 'Belum dihubungkan ke orang tua' : 'Pilih orang tua atau buat baru di bawah' }} —</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id_ortu }}" @selected((string) old('parent_id', $student?->parent_id) === (string) $parent->id_ortu)>
                                    {{ $parent->name }}{{ $parent->phone ? ' · '.$parent->phone : '' }}{{ $parent->email ? ' · '.$parent->email : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">Gunakan pencarian di dalam dropdown untuk memilih orang tua.</span>
                        @error('parent_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    @if(! $student)
                        <div class="field">
                            <label class="label" for="parent_name">Atau buat orang tua baru (Nama)</label>
                            <input type="text" name="parent_name" id="parent_name" class="input" value="{{ old('parent_name') }}" placeholder="Isi jika orang tua belum terdaftar" maxlength="100">
                            @error('parent_name') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="field">
                            <label class="label" for="parent_phone">Telepon orang tua baru</label>
                            <input type="text" name="parent_phone" id="parent_phone" class="input" value="{{ old('parent_phone') }}" placeholder="Contoh: 62812xxxx" maxlength="30">
                            @error('parent_phone') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="field field-full">
                        <label class="label" for="class_id">Penempatan kelas (opsional)</label>
                        <select name="class_id" id="class_id" class="select" data-searchable-select data-placeholder="Ketik nama kelas untuk mencari...">
                            <option value="">— Belum ditempatkan ke kelas —</option>
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id_kelas }}" @selected((string) old('class_id', $student?->class_id) === (string) $classRoom->id_kelas)>
                                    {{ $classRoom->name }} ({{ $classRoom->academic_year }})
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">Opsional (nullable). Siswa juga dapat dimasukkan ke kelas melalui checklist pada halaman detail kelas.</span>
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ request()->query('from') === 'class' && $student?->class_id ? route('classes.show', $student->class_id) : route('students.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $student ? 'Simpan perubahan' : 'Simpan siswa' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
