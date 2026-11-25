@extends('layouts.app')

@section('title', $announcement ? 'Ubah Pemberitahuan' : 'Buat Pemberitahuan')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('announcements.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke pemberitahuan</a>
            <h1 class="page-title">{{ $announcement ? 'Ubah pemberitahuan' : 'Buat pemberitahuan' }}</h1>
            <p class="page-sub">Sampaikan informasi atau agenda penting untuk kelas.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $announcement ? route('announcements.update', $announcement) : route('announcements.store') }}">
                @csrf
                @if($announcement)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="title">Judul pemberitahuan <span class="req">*</span></label>
                        <input type="text" name="title" id="title" class="input" value="{{ old('title', $announcement?->title) }}" placeholder="Contoh: Informasi rapat kelas" required maxlength="255">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="class_id">Kelas <span class="req">*</span></label>
                        @if($user->isAdmin() || $classes->count() > 1)
                            <select name="class_id" id="class_id" class="select" required>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((string) old('class_id', $announcement?->class_id) === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="class_id" value="{{ $classes->first()?->id }}">
                            <input type="text" class="input" value="{{ $classes->first()?->name }}" disabled>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="body">Isi pemberitahuan <span class="req">*</span></label>
                        <textarea name="body" id="body" class="textarea" rows="6" maxlength="5000" placeholder="Tuliskan informasi lengkap." required>{{ old('body', $announcement?->keterangan) }}</textarea>
                        @error('body') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('announcements.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $announcement ? 'Simpan perubahan' : 'Simpan pemberitahuan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
