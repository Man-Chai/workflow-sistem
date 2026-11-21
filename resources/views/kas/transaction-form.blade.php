@extends('layouts.app')

@section('title', 'Catat Transaksi')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ route('cash.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kas</a>
            <h1 class="page-title">Catat transaksi kas</h1>
            <p class="page-sub">Catat pemasukan atau pengeluaran kelas. Pastikan nominal dan keterangan sesuai bukti.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ route('cash.store') }}">
                @csrf

                <div class="field">
                    <span class="label">Jenis transaksi <span class="req">*</span></span>
                    <div class="segmented">
                        <label>
                            <input type="radio" name="type" value="masuk" @checked(old('type', 'masuk') === 'masuk')>
                            <span><x-icon name="arrow-down-left" :size="15" /> Kas masuk</span>
                        </label>
                        <label>
                            <input type="radio" name="type" value="keluar" @checked(old('type') === 'keluar')>
                            <span><x-icon name="arrow-up-right" :size="15" /> Kas keluar</span>
                        </label>
                    </div>
                    @error('type') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="class_id">Kelas <span class="req">*</span></label>
                        @if($user->isAdmin())
                            <select name="class_id" id="class_id" class="select" required>
                                <option value="">Pilih kelas</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((int) old('class_id') === $class->id)>{{ $class->name }} · {{ $class->academic_year }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="input" value="{{ $classes->first()?->name }} · {{ $classes->first()?->academic_year }}" disabled>
                            <span class="hint">Transaksi otomatis tercatat pada kelas yang Anda koordinasikan.</span>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="transaction_date">Tanggal transaksi <span class="req">*</span></label>
                        <input type="date" name="transaction_date" id="transaction_date" class="input" max="{{ now()->format('Y-m-d') }}" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                        @error('transaction_date') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="category">Kategori <span class="req">*</span></label>
                        <input type="text" name="category" id="category" class="input" list="category-options" value="{{ old('category') }}" placeholder="Contoh: Peralatan kelas" required maxlength="60">
                        <datalist id="category-options">
                            <option value="Iuran">
                            <option value="Donasi">
                            <option value="Saldo awal">
                            <option value="Peralatan kelas">
                            <option value="Kebersihan">
                            <option value="Konsumsi rapat">
                            <option value="ATK">
                            <option value="Kegiatan kelas">
                            <option value="Lainnya">
                        </datalist>
                        @error('category') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="amount">Nominal <span class="req">*</span></label>
                        <input type="number" name="amount" id="amount" class="input" min="1" step="1000" value="{{ old('amount') }}" placeholder="50000" required>
                        <span class="hint">Tulis tanpa titik atau koma, contoh 50000.</span>
                        @error('amount') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Keterangan</label>
                        <textarea name="description" id="description" class="textarea" maxlength="500" placeholder="Contoh: Pembelian spidol dan penghapus untuk papan kelas.">{{ old('description') }}</textarea>
                        <span class="hint">Keterangan yang jelas memudahkan orang tua memahami penggunaan kas.</span>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('cash.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        Simpan transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
