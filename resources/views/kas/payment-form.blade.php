@extends('layouts.app')

@section('title', 'Catat Pembayaran')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('cash.index', ['class' => $due->class_id, 'tab' => 'iuran']) }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke iuran</a>
            <h1 class="page-title">Catat pembayaran iuran</h1>
            <p class="page-sub">Pembayaran yang dicatat koordinator langsung masuk ke kas kelas.</p>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="wallet" :size="16" /> Detail pembayaran</h3>
                    <p class="card-sub">Pilih siswa, isi nominal, lalu simpan. Bukti pembayaran bersifat opsional.</p>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('payments.create') }}" class="filter-bar" style="margin-top: 0;">
                    <input type="hidden" name="due" value="{{ $due->id }}">
                    <span class="filter-label">Siswa</span>
                    <select name="student" class="select" data-auto-submit aria-label="Pilih siswa">
                        @foreach($students as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $student->id)>
                                {{ $option->name }} · NIS {{ $option->nis }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline">Tampilkan</button>
                </form>

                @if($remaining <= 0)
                    <div class="alert alert-warning">
                        <x-icon name="circle-check" :size="18" />
                        <div>Iuran ini sudah lunas untuk {{ $student->name }}. Pilih siswa lain bila ingin mencatat pembayaran.</div>
                    </div>
                @else
                    <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="due_id" value="{{ $due->id }}">
                        <input type="hidden" name="student_id" value="{{ $student->id }}">

                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="amount">Nominal dibayar <span class="req">*</span></label>
                                <input type="number" name="amount" id="amount" class="input" min="1000" step="1000" max="{{ $remaining }}" value="{{ old('amount', $remaining) }}" required>
                                <span class="hint">Sisa tagihan: <strong>{{ rupiah($remaining) }}</strong>. Pembayaran sebagian diperbolehkan.</span>
                                @error('amount') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="label" for="paid_at">Tanggal bayar <span class="req">*</span></label>
                                <input type="date" name="paid_at" id="paid_at" class="input" max="{{ now()->format('Y-m-d') }}" value="{{ old('paid_at', now()->format('Y-m-d')) }}" required>
                                @error('paid_at') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="label" for="method">Metode pembayaran <span class="req">*</span></label>
                                <select name="method" id="method" class="select" required>
                                    <option value="transfer" @selected(old('method') === 'transfer')>Transfer bank</option>
                                    <option value="qris" @selected(old('method') === 'qris')>QRIS</option>
                                    <option value="tunai" @selected(old('method') === 'tunai')>Tunai</option>
                                </select>
                                @error('method') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="label" for="proof">Bukti pembayaran</label>
                                <input type="file" name="proof" id="proof" class="input" accept=".jpg,.jpeg,.png,.pdf">
                                <span class="hint">Opsional. JPG, PNG, atau PDF maksimal 2 MB.</span>
                                @error('proof') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field field-full">
                                <label class="label" for="note">Catatan</label>
                                <textarea name="note" id="note" class="textarea" maxlength="500" placeholder="Contoh: Transfer BCA atas nama orang tua siswa.">{{ old('note') }}</textarea>
                                @error('note') <span class="error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="notice mb-2">
                            <x-icon name="info" :size="15" />
                            <span>Pembayaran akan langsung tercatat sebagai pemasukan kas kelas. Pastikan nominal dan tanggal sudah benar.</span>
                        </div>

                        <div class="form-actions" style="margin-top: 0; padding-top: 0; border-top: 0;">
                            <a href="{{ route('cash.index', ['class' => $due->class_id, 'tab' => 'iuran']) }}" class="btn btn-ghost">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <x-icon name="check" :size="15" />
                                Simpan pembayaran
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="receipt" :size="16" /> Ringkasan tagihan</h3>
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="page-title" style="font-size: 21px;">{{ $due->title }}</h3>
                    <p class="small muted mt-1">{{ $due->period ?: 'Tanpa periode' }} · {{ $due->classRoom?->name }}</p>

                    <div class="info-grid mt-3">
                        <div class="info-item">
                            <span>Nominal iuran</span>
                            <strong>{{ rupiah($due->amount) }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Sisa tagihan</span>
                            <strong class="text-out">{{ rupiah($remaining) }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Jatuh tempo</span>
                            <strong>{{ $due->due_date->format('d M Y') }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Status</span>
                            <strong>{{ $due->isOverdue() ? 'Lewat jatuh tempo' : 'Masih berlaku' }}</strong>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="child-card">
                        <x-avatar :name="$student->name" size="lg" />
                        <div class="child-meta">
                            <strong>{{ $student->name }}</strong>
                            <span>NIS {{ $student->nis }}</span>
                            <span>{{ $student->classRoom?->name }}</span>
                        </div>
                    </div>

                    @if($due->description)
                        <div class="notice mt-3">
                            <x-icon name="info" :size="15" />
                            <span>{{ $due->description }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
