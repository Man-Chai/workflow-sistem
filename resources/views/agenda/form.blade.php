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
            <p class="page-sub">Agenda disimpan ke tabel <code>agenda</code> dan otomatis memicu webhook pengingat n8n.</p>
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
                        <input type="text" name="title" id="title" class="input" value="{{ old('title', $agenda?->title) }}" placeholder="Contoh: Rapat koordinasi orang tua" required maxlength="255">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="class_id">Kelas <span class="req">*</span></label>
                        @if($user->isAdmin() || $classes->count() > 1)
                            <select name="class_id" id="class_id" class="select" required>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((string) old('class_id', $agenda?->class_id) === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="class_id" value="{{ $classes->first()?->id }}">
                            <input type="text" class="input" value="{{ $classes->first()?->name }}" disabled>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="starts_at">Mulai <span class="req">*</span></label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="input" value="{{ old('starts_at', $agenda?->starts_at?->format('Y-m-d\TH:i')) }}" required>
                        @error('starts_at') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="ends_at">Selesai</label>
                        <input type="datetime-local" name="ends_at" id="ends_at" class="input" value="{{ old('ends_at', $agenda?->ends_at?->format('Y-m-d\TH:i')) }}">
                        @error('ends_at') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Keterangan</label>
                        <textarea name="description" id="description" class="textarea" maxlength="2000" placeholder="Tuliskan tujuan, persiapan, atau informasi penting lainnya.">{{ old('description', $agenda?->description) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
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
