@extends('layouts.app')

@section('title', $classRoom ? 'Ubah Kelas' : 'Tambah Kelas')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('classes.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kelas</a>
            <h1 class="page-title">{{ $classRoom ? 'Ubah kelas' : 'Tambah kelas' }}</h1>
            <p class="page-sub">{{ $classRoom ? 'Perbarui nama kelas dan koordinator penanggung jawab.' : 'Buat kelas baru, lalu tambahkan siswa dan koordinatornya.' }}</p>
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
                    <div class="field field-full">
                        <label class="label" for="name">Nama kelas <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $classRoom?->name) }}" placeholder="Contoh: XII MIPA 1" required maxlength="100">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="coordinator_id">Koordinator / Pengurus kelas</label>
                        <select name="coordinator_id" id="coordinator_id" class="select" data-searchable-select data-placeholder="Ketik nama atau email koordinator...">
                            <option value="">Belum ditugaskan</option>
                            @foreach($coordinators as $coordinator)
                                <option value="{{ $coordinator->id }}" @selected((string) old('coordinator_id', $classRoom?->coordinator_id) === (string) $coordinator->id)>
                                    {{ $coordinator->name }} ({{ $coordinator->roleLabel() }}) · {{ $coordinator->email }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">Penugasan disimpan pada tabel <code>kelas_user</code>.</span>
                        @error('coordinator_id') <span class="error">{{ $message }}</span> @enderror
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
