@extends('layouts.app')

@section('title', $due ? 'Ubah Iuran' : 'Buat Iuran')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('cash.index', ['class' => $due?->class_id, 'tab' => 'iuran']) }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke iuran</a>
            <h1 class="page-title">{{ $due ? 'Ubah iuran' : 'Buat iuran baru' }}</h1>
            <p class="page-sub">Iuran akan muncul di akun orang tua dan dapat dibayar sesuai sisa tagihan.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $due ? route('dues.update', $due) : route('dues.store') }}">
                @csrf
                @if($due)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="title">Nama iuran <span class="req">*</span></label>
                        <input type="text" name="title" id="title" class="input" value="{{ old('title', $due?->title) }}" placeholder="Contoh: Kas Kelas November" required maxlength="120">
                        @error('title') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="class_id">Kelas <span class="req">*</span></label>
                        @if($due)
                            @php
                                $dueClass = $classes->firstWhere('id', $due->class_id);
                            @endphp
                            <input type="text" id="class_id" class="input" value="{{ $dueClass?->name }} · {{ $dueClass?->academic_year }}" disabled>
                            <span class="hint">Kelas iuran tidak dapat diubah setelah iuran dibuat.</span>
                        @elseif($user->isAdmin())
                            <select name="class_id" id="class_id" class="select" required>
                                <option value="">Pilih kelas</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_id') === $class->id)>{{ $class->name }} · {{ $class->academic_year }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="input" value="{{ $classes->first()?->name }} · {{ $classes->first()?->academic_year }}" disabled>
                            <span class="hint">Iuran otomatis tercatat pada kelas yang Anda koordinasikan.</span>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="period">Periode</label>
                        <input type="text" name="period" id="period" class="input" value="{{ old('period', $due?->period) }}" placeholder="Contoh: November 2026" maxlength="40">
                        @error('period') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="amount">Nominal per siswa <span class="req">*</span></label>
                        <input type="number" name="amount" id="amount" class="input" min="1000" step="1000" value="{{ old('amount', $due?->amount) }}" placeholder="50000" required>
                        <span class="hint">Target kelas = nominal × jumlah siswa aktif.</span>
                        @error('amount') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="due_date">Jatuh tempo <span class="req">*</span></label>
                        <input type="date" name="due_date" id="due_date" class="input" value="{{ old('due_date', $due?->due_date?->format('Y-m-d')) }}" required>
                        @error('due_date') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="textarea" maxlength="500" placeholder="Jelaskan peruntukan iuran agar orang tua memahami.">{{ old('description', $due?->description) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="checkbox">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $due?->is_active ?? true))>
                            <span>
                                <strong>Iuran aktif</strong>
                                <small>Iuran aktif akan tampil sebagai tagihan di akun orang tua.</small>
                            </span>
                        </label>
                        @error('is_active') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('cash.index', ['class' => $due?->class_id, 'tab' => 'iuran']) }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $due ? 'Simpan perubahan' : 'Buat iuran' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
