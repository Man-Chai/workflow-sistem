@extends('layouts.app')

@section('title', $classRoom ? 'Ubah Kelas' : 'Tambah Kelas')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('classes.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kelas</a>
            <h1 class="page-title">{{ $classRoom ? 'Ubah kelas' : 'Tambah kelas' }}</h1>
            <p class="page-sub">{{ $classRoom ? 'Perbarui identitas kelas dan koordinator penanggung jawab.' : 'Buat kelas baru, lalu tambahkan siswa dan koordinatornya.' }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $classRoom ? route('classes.update', $classRoom) : route('classes.store') }}">
                @csrf
                @if($classRoom)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="name">Nama kelas <span class="req">*</span></label>
                        <select name="name" id="name" class="select" required>
                            <option value="">Pilih kelas</option>
                            @foreach(\App\Models\ClassRoom::LEVELS as $level)
                                <option value="{{ $level }}" @selected(old('name', $classRoom?->name) === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        <span class="hint">Kelas diseragamkan menjadi Kelas 1 sampai Kelas 6.</span>
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="academic_year">Tahun ajaran <span class="req">*</span></label>
                        <input type="text" name="academic_year" id="academic_year" class="input" value="{{ old('academic_year', $classRoom?->academic_year ?? '2026/2027') }}" placeholder="2026/2027" required maxlength="20">
                        @error('academic_year') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="coordinator_id">Koordinator kelas</label>
                        <select name="coordinator_id" id="coordinator_id" class="select">
                            <option value="">Belum ditugaskan</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}" @selected((int) old('coordinator_id', $classRoom?->coordinator_id) === $coordinator->id)>
                                    {{ $coordinator->name }} · {{ $coordinator->email }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">Hanya pengguna dengan peran koordinator yang muncul di sini.</span>
                        @error('coordinator_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Deskripsi kelas</label>
                        <textarea name="description" id="description" class="textarea" maxlength="1000" placeholder="Deskripsi singkat kelas, program, atau catatan khusus.">{{ old('description', $classRoom?->description) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('classes.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $classRoom ? 'Simpan perubahan' : 'Buat kelas' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
