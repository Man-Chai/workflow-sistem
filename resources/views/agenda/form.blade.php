@extends('layouts.app')

@section('title', $agenda ? 'Ubah Agenda' : 'Tambah Agenda')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('agenda.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke agenda</a>
            <h1 class="page-title">{{ $agenda ? 'Ubah agenda' : 'Tambah agenda' }}</h1>
            <p class="page-sub">Agenda akan tampil pada dashboard anggota kelas sesuai cakupan yang dipilih.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $agenda ? route('agenda.update', $agenda) : route('agenda.store') }}">
                @csrf
                @if($agenda)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="title">Judul agenda <span class="req">*</span></label>
                        <input type="text" name="title" id="title" class="input" value="{{ old('title', $agenda?->title) }}" placeholder="Contoh: Rapat koordinasi orang tua" required maxlength="140">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="class_id">Cakupan <span class="req">*</span></label>
                        @if($user->isAdmin())
                            <select name="class_id" id="class_id" class="select">
                                <option value="">Semua kelas (agenda sekolah)</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_id', $agenda?->class_id) === $class->id)>{{ $class->name }} · {{ $class->academic_year }}</option>
                                @endforeach
                            </select>
                            <span class="hint">Kosongkan untuk agenda yang berlaku di seluruh sekolah.</span>
                        @else
                            <input type="text" class="input" value="{{ $classes->first()?->name }} · {{ $classes->first()?->academic_year }}" disabled>
                            <span class="hint">Agenda otomatis tercatat pada kelas yang Anda koordinasikan.</span>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="location">Lokasi</label>
                        <input type="text" name="location" id="location" class="input" value="{{ old('location', $agenda?->location) }}" placeholder="Contoh: Ruang kelas X IPA 1" maxlength="140">
                        @error('location') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="starts_at">Mulai <span class="req">*</span></label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="input" value="{{ old('starts_at', $agenda?->starts_at?->format('Y-m-d\TH:i')) }}" required>
                        @error('starts_at') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="ends_at">Selesai</label>
                        <input type="datetime-local" name="ends_at" id="ends_at" class="input" value="{{ old('ends_at', $agenda?->ends_at?->format('Y-m-d\TH:i')) }}">
                        <span class="hint">Opsional, harus setelah waktu mulai.</span>
                        @error('ends_at') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="textarea" maxlength="2000" placeholder="Tuliskan tujuan, persiapan, atau informasi penting lainnya.">{{ old('description', $agenda?->description) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="checkbox">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $agenda?->is_published ?? true))>
                            <span>
                                <strong>Terbitkan agenda</strong>
                                <small>Jika dimatikan, agenda tidak tampil untuk orang tua.</small>
                            </span>
                        </label>
                        @error('is_published') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('agenda.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $agenda ? 'Simpan perubahan' : 'Simpan agenda' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
