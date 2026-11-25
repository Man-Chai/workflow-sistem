@php
    $isEdit = isset($transaction) && $transaction !== null;
    $user = auth()->user();
    $selectedClass = $isEdit ? $transaction->id_kelas : request('class');
    $rawDesc = $isEdit ? (string) $transaction->description : '';
    if ($isEdit && str_contains($rawDesc, ' — ')) {
        [$defaultCategory, $defaultDescription] = explode(' — ', $rawDesc, 2);
    } else {
        $defaultCategory = $isEdit ? ($transaction->category ?: $rawDesc) : '';
        $defaultDescription = $isEdit && ! str_starts_with($rawDesc, 'Pembayaran kas/iuran siswa:') && $rawDesc !== $defaultCategory ? $rawDesc : '';
    }
@endphp
@extends('layouts.app')

@section('title', $isEdit ? 'Ubah Transaksi' : 'Catat Transaksi')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('cash.index', ['class' => $selectedClass]) }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke kas</a>
            <h1 class="page-title">{{ $isEdit ? 'Ubah transaksi laporan keuangan' : 'Catat transaksi laporan keuangan' }}</h1>
            <p class="page-sub">Catat pemasukan (debit) atau pengeluaran (kredit) kelas ke tabel <code>laporan_keuangan</code>.</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('cash.update', $transaction) : route('cash.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="field">
                    <span class="label">Jenis transaksi <span class="req">*</span></span>
                    <div class="segmented">
                        <label>
                            <input type="radio" name="type" value="masuk" @checked(old('type', $isEdit ? $transaction->type : 'masuk') === 'masuk')>
                            <span><x-icon name="arrow-down-left" :size="15" /> Debit (Kas masuk)</span>
                        </label>
                        <label>
                            <input type="radio" name="type" value="keluar" @checked(old('type', $isEdit ? $transaction->type : '') === 'keluar') @disabled($isEdit && !empty($transaction->id_pembukuan))>
                            <span><x-icon name="arrow-up-right" :size="15" /> Kredit (Kas keluar)</span>
                        </label>
                    </div>
                    @error('type') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="class_id">Kelas <span class="req">*</span></label>
                        @if($user->isAdmin() || $classes->count() > 1)
                            <select name="class_id" id="class_id" class="select" required>
                                <option value="">Pilih kelas</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" @selected((string) old('class_id', $selectedClass) === (string) $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="class_id" value="{{ $classes->first()?->id }}">
                            <input type="text" class="input" value="{{ $classes->first()?->name }}" disabled>
                        @endif
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="transaction_date">Tanggal transaksi <span class="req">*</span></label>
                        <input type="date" name="transaction_date" id="transaction_date" class="input" max="{{ now()->format('Y-m-d') }}" value="{{ old('transaction_date', $isEdit ? $transaction->tanggal?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
                        @error('transaction_date') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="category">Kategori <span class="req">*</span></label>
                        <input type="text" name="category" id="category" class="input" list="category-options" value="{{ old('category', $defaultCategory) }}" placeholder="Contoh: Peralatan kelas" required maxlength="60">
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
                        <input type="number" name="amount" id="amount" class="input" min="1" step="any" value="{{ old('amount', $isEdit ? $transaction->amount : '') }}" placeholder="50000" required>
                        @error('amount') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="description">Keterangan</label>
                        <textarea name="description" id="description" class="textarea" maxlength="500" placeholder="Contoh: Pembelian spidol dan penghapus untuk papan kelas.">{{ old('description', $defaultDescription) }}</textarea>
                        @error('description') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('cash.index', ['class' => $selectedClass]) }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $isEdit ? 'Simpan perubahan' : 'Simpan transaksi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
