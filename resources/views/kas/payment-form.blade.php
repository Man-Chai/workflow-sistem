@php
    $isEdit = isset($payment) && $payment !== null;
@endphp
@extends('layouts.app')

@section('title', $isEdit ? 'Ubah Pembukuan Siswa' : 'Catat Pembukuan Siswa')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('cash.index', ['class' => $classRoom->id, 'tab' => 'pembayaran']) }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kas</a>
            <h1 class="page-title">{{ $isEdit ? 'Ubah pembukuan siswa' : 'Catat pembukuan siswa' }}</h1>
            <p class="page-sub">Data disimpan ke tabel <code>pembukuan</code> dan otomatis tersinkronisasi sebagai debit di <code>laporan_keuangan</code>.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            @if(! $isEdit && $classes->count() > 1)
                <form method="GET" action="{{ route('payments.create') }}" class="filter-bar" style="margin-top: 0;">
                    <span class="filter-label">Kelas</span>
                    <select name="class" class="select" data-auto-submit aria-label="Pilih kelas">
                        @foreach($classes as $option)
                            <option value="{{ $option->id }}" @selected((string) $option->id === (string) $classRoom->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if($students->isEmpty())
                <x-empty icon="users" title="Belum ada siswa di kelas {{ $classRoom->name }}" text="Tambahkan siswa terlebih dahulu di menu Kelas & Siswa.">
                    <a href="{{ route('classes.show', $classRoom) }}" class="btn btn-sm btn-primary">Kelola siswa</a>
                </x-empty>
            @else
                <form method="POST" action="{{ $isEdit ? route('payments.update', $payment) : route('payments.store') }}">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="form-grid">
                        <div class="field field-full">
                            <label class="label" for="student_id">Pilih siswa ({{ $classRoom->name }}) <span class="req">*</span></label>
                            <select name="student_id" id="student_id" class="select" required>
                                @foreach($students as $option)
                                    <option value="{{ $option->id }}" @selected((string) old('student_id', $student?->id) === (string) $option->id)>
                                        {{ $option->name }}{{ $option->ortu ? ' (Ortu: '.$option->ortu->name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="field field-full">
                            <label class="label" for="amount">Nominal pembayaran (Rp) <span class="req">*</span></label>
                            <input type="number" name="amount" id="amount" class="input" min="1" step="any" value="{{ old('amount', $isEdit ? $payment->amount : 50000) }}" required>
                            @error('amount') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('cash.index', ['class' => $classRoom->id, 'tab' => 'pembayaran']) }}" class="btn btn-ghost">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check" :size="15" />
                            {{ $isEdit ? 'Simpan perubahan' : 'Simpan pembukuan' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
