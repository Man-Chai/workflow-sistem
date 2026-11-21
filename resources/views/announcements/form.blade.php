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
            <p class="page-sub">Sampaikan informasi penting dengan prioritas dan cakupan yang tepat.</p>
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
                        <input type="text" name="title" id="title" class="input" value="{{ old('title', $announcement?->title) }}" placeholder="Contoh: Batas pembayaran kas November" required maxlength="160">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="class_id">Cakupan</label>
                        @if($user->isAdmin())
                            <select name="class_id" id="class_id" class="select">
                                <option value="">Semua kelas (pemberitahuan sekolah)</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_id', $announcement?->class_id) === $class->id)>{{ $class->name }} · {{ $class->academic_year }}</option>
                                @endforeach
                            </select>
                            <span class="hint">Pilih kelas tertentu atau biarkan kosong untuk seluruh sekolah.</span>
                        @else
                            <input type="text" class="input" value="{{ $classes->first()?->name }} · {{ $classes->first()?->academic_year }}" disabled>
                            <span class="hint">Pemberitahuan otomatis ditujukan ke kelas yang Anda koordinasikan.</span>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="priority">Prioritas <span class="req">*</span></label>
                        <select name="priority" id="priority" class="select" required>
                            <option value="normal" @selected(old('priority', $announcement?->priority ?? 'normal') === 'normal')>Informasi biasa</option>
                            <option value="penting" @selected(old('priority', $announcement?->priority) === 'penting')>Penting</option>
                            <option value="mendesak" @selected(old('priority', $announcement?->priority) === 'mendesak')>Mendesak</option>
                        </select>
                        <span class="hint">Gunakan "Mendesak" hanya untuk hal yang membutuhkan tindakan cepat.</span>
                        @error('priority') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="body">Isi pemberitahuan <span class="req">*</span></label>
                        <textarea name="body" id="body" class="textarea" rows="8" maxlength="5000" placeholder="Tuliskan informasi lengkap, tanggal penting, dan tindakan yang perlu dilakukan orang tua." required>{{ old('body', $announcement?->body) }}</textarea>
                        @error('body') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement?->is_pinned ?? false))>
                            <span>
                                <strong>Sematkan di atas</strong>
                                <small>Pemberitahuan akan selalu tampil paling atas.</small>
                            </span>
                        </label>
                        @error('is_pinned') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="checkbox">
                            <input type="checkbox" name="publish" value="1" @checked(old('publish', $announcement?->published_at !== null || $announcement === null))>
                            <span>
                                <strong>Terbitkan sekarang</strong>
                                <small>Jika dimatikan, pemberitahuan disimpan sebagai draf.</small>
                            </span>
                        </label>
                        @error('publish') <span class="error">{{ $message }}</span> @enderror
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
