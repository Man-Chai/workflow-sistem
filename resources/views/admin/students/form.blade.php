@extends('layouts.app')

@section('title', 'Ubah Siswa')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('classes.show', $student->class_id) }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kelas</a>
            <h1 class="page-title">Ubah data siswa</h1>
            <p class="page-sub">{{ $student->classRoom?->name }} · {{ $student->classRoom?->academic_year }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ route('students.update', $student) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="name">Nama siswa <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $student->name) }}" required maxlength="120">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="nis">NIS <span class="req">*</span></label>
                        <input type="text" name="nis" id="nis" class="input" value="{{ old('nis', $student->nis) }}" required maxlength="30">
                        @error('nis') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="gender">Jenis kelamin</label>
                        <select name="gender" id="gender" class="select">
                            <option value="L" @selected(old('gender', $student->gender) === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('gender', $student->gender) === 'P')>Perempuan</option>
                        </select>
                        @error('gender') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="birth_date">Tanggal lahir</label>
                        <input type="date" name="birth_date" id="birth_date" class="input" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}">
                        @error('birth_date') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="parent_id">Akun orang tua</label>
                        <select name="parent_id" id="parent_id" class="select">
                            <option value="">Belum dihubungkan</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((int) old('parent_id', $student->parent_id) === $parent->id)>{{ $parent->name }} · {{ $parent->email }}</option>
                            @endforeach
                        </select>
                        <span class="hint">Orang tua akan melihat agenda dan pemberitahuan kelas siswa.</span>
                        @error('parent_id') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('classes.show', $student->class_id) }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        Simpan perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
