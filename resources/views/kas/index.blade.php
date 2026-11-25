@extends('layouts.app')

@section('title', 'Kas Kelas')

@section('content')
    @php
        $validMonth = $month && preg_match('/^\d{4}-\d{2}$/', $month);
        $monthLabel = $validMonth
            ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y')
            : now()->translatedFormat('F Y');
        $monthValue = $validMonth ? $month : now()->format('Y-m');
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="wallet" :size="13" /> Transparansi keuangan</span>
            <h1 class="page-title">Kas & Pembukuan Kelas</h1>
            <p class="page-sub">
                @if($class)
                    Laporan keuangan (`laporan_keuangan`) dan pembukuan siswa (`pembukuan`) {{ $class->name }}.
                @else
                    Pantau dan kelola keuangan kelas Anda.
                @endif
            </p>
        </div>
        @if($class)
            <div class="page-actions">
                <a href="{{ route('cash.export', ['class' => $class->id]) }}" class="btn btn-outline">
                    <x-icon name="download" :size="16" />
                    Unduh CSV
                </a>
                <a href="{{ route('payments.create', ['class' => $class->id]) }}" class="btn btn-outline">
                    <x-icon name="wallet" :size="16" />
                    Catat pembukuan siswa
                </a>
                <a href="{{ route('cash.create', ['class' => $class->id]) }}" class="btn btn-primary">
                    <x-icon name="plus" :size="16" />
                    Catat transaksi
                </a>
            </div>
        @endif
    </div>

    @if(! $class)
        <div class="card">
            <div class="card-body">
                <x-empty icon="wallet" title="Belum ada kelas terhubung" text="Akun Anda belum terhubung ke kelas mana pun.">
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline">Kembali ke dashboard</a>
                </x-empty>
            </div>
        </div>
    @else
        @if($classes->count() > 1)
            <form method="GET" action="{{ route('cash.index') }}" class="filter-bar">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <span class="filter-label">Kelas</span>
                <select name="class" class="select" data-auto-submit aria-label="Pilih kelas">
                    @foreach($classes as $classOption)
                        <option value="{{ $classOption->id }}" @selected((string) $classOption->id === (string) $class->id)>
                            {{ $classOption->name }}
                        </option>
                    @endforeach
                </select>
                @if($month)
                    <input type="hidden" name="month" value="{{ $month }}">
                @endif
            </form>
        @endif

        <div class="summary-grid">
            <div class="hero-card">
                <span class="eyebrow"><x-icon name="wallet" :size="13" /> Saldo kas {{ $class->name }}</span>
                <h2>{{ rupiah($balance) }}</h2>
                <p>Saldo berjalan dari tabel <code>laporan_keuangan</code>.</p>
                <div class="hero-row">
                    <div class="hero-stat">
                        <span>Total debit (masuk)</span>
                        <strong>{{ rupiah($income) }}</strong>
                    </div>
                    <div class="hero-stat">
                        <span>Total kredit (keluar)</span>
                        <strong>{{ rupiah($expense) }}</strong>
                    </div>
                    <div class="hero-stat">
                        <span>Jumlah siswa</span>
                        <strong>{{ $class->students_count }} siswa</strong>
                    </div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Debit {{ $monthLabel }}</span>
                    <span class="stat-icon"><x-icon name="arrow-down-left" :size="17" /></span>
                </div>
                <div class="stat-value text-in">{{ rupiah($monthIncome) }}</div>
                <div class="stat-meta">Kas masuk pada periode ini</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Kredit {{ $monthLabel }}</span>
                    <span class="stat-icon is-rose"><x-icon name="receipt" :size="17" /></span>
                </div>
                <div class="stat-value text-out">{{ rupiah($monthExpense) }}</div>
                <div class="stat-meta">Kas keluar pada periode ini</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Pembukuan siswa</span>
                    <span class="stat-icon is-amber"><x-icon name="users" :size="17" /></span>
                </div>
                <div class="stat-value">{{ $payments->total() }}</div>
                <div class="stat-meta">Total catatan di tabel <code>pembukuan</code></div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="tabs">
                <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'buku', 'month' => $month]) }}" class="tab {{ $tab === 'buku' ? 'is-active' : '' }}">
                    <x-icon name="receipt" :size="14" /> Laporan Keuangan
                </a>
                <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']) }}" class="tab {{ $tab === 'pembayaran' ? 'is-active' : '' }}">
                    <x-icon name="wallet" :size="14" /> Pembukuan Siswa
                    <span class="tab-count">{{ $payments->total() }}</span>
                </a>
            </div>
        </div>

        @if($tab === 'buku')
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="receipt" :size="16" /> Laporan Keuangan (<code>laporan_keuangan</code>)</h3>
                        <p class="card-sub">Riwayat debit, kredit, dan saldo berjalan.</p>
                    </div>
                    <form method="GET" action="{{ route('cash.index') }}" class="filter-right">
                        <input type="hidden" name="class" value="{{ $class->id }}">
                        <input type="hidden" name="tab" value="buku">
                        <input type="month" name="month" value="{{ $monthValue }}" class="input" aria-label="Filter bulan">
                        <button type="submit" class="btn btn-sm btn-outline">Terapkan</button>
                        @if($validMonth)
                            <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'buku']) }}" class="btn btn-sm btn-ghost">Reset</a>
                        @endif
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Jenis</th>
                                    <th>Nominal</th>
                                    <th>Saldo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="nowrap">
                                            <span class="cell-main">{{ $transaction->transaction_date->format('d M') }}</span>
                                            <span class="cell-sub">{{ $transaction->transaction_date->format('Y') }}</span>
                                        </td>
                                        <td>
                                            <span class="cell-main">{{ $transaction->description ?: ucfirst($transaction->type) }}</span>
                                            <span class="cell-sub">{{ $transaction->category }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $transaction->type === 'masuk' ? 'badge-success' : 'badge-danger' }}">
                                                {{ $transaction->type === 'masuk' ? 'Debit (Masuk)' : 'Kredit (Keluar)' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount {{ $transaction->type === 'masuk' ? 'text-in' : 'text-out' }}">
                                                {{ $transaction->type === 'masuk' ? '+' : '−' }} {{ rupiah($transaction->amount) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount">{{ rupiah((int) round((float) $transaction->saldo)) }}</span>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <a href="{{ route('cash.edit', $transaction) }}" class="icon-btn" aria-label="Ubah transaksi" style="width: 29px; height: 29px;">
                                                    <x-icon name="edit" :size="14" />
                                                </a>
                                                <form method="POST" action="{{ route('cash.destroy', $transaction) }}" data-confirm="Hapus transaksi laporan keuangan ini?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" aria-label="Hapus transaksi" style="width: 29px; height: 29px;">
                                                        <x-icon name="trash" :size="14" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-empty icon="receipt" title="Belum ada transaksi di laporan_keuangan" text="Catat transaksi kas atau pembukuan siswa untuk mengisi laporan keuangan.">
                                                <a href="{{ route('cash.create', ['class' => $class->id]) }}" class="btn btn-sm btn-primary">Catat transaksi</a>
                                            </x-empty>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $transactions->links() }}
                </div>
            </div>
        @endif

        @if($tab === 'pembayaran')
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="wallet" :size="16" /> Pembukuan Siswa (<code>pembukuan</code>)</h3>
                        <p class="card-sub">Setiap pembayaran siswa di tabel <code>pembukuan</code> otomatis menambah debit di <code>laporan_keuangan</code> melalui trigger PostgreSQL.</p>
                    </div>
                    <a href="{{ route('payments.create', ['class' => $class->id]) }}" class="btn btn-sm btn-primary">
                        <x-icon name="plus" :size="13" /> Catat pembukuan
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <x-avatar :name="$payment->student?->name ?? 'Siswa'" size="sm" />
                                                <span>
                                                    <span class="cell-main">{{ $payment->student?->name }}</span>
                                                    <span class="cell-sub">{{ $payment->student?->ortu?->name ? 'Ortu: '.$payment->student->ortu->name : '' }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-neutral">{{ $class->name }}</span></td>
                                        <td class="nowrap">{{ $payment->paid_at->format('d M Y · H:i') }}</td>
                                        <td><span class="amount text-in">+ {{ rupiah($payment->amount) }}</span></td>
                                        <td><span class="badge badge-success">Tercatat</span></td>
                                        <td>
                                            <div class="row-actions">
                                                <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-ghost">Ubah</a>
                                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Hapus catatan pembukuan {{ $payment->student?->name }}?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-ghost">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-empty icon="receipt" title="Belum ada data pembukuan" text="Catat pembayaran siswa ke tabel pembukuan.">
                                                <a href="{{ route('payments.create', ['class' => $class->id]) }}" class="btn btn-sm btn-primary">Catat pembukuan</a>
                                            </x-empty>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $payments->links() }}
                </div>
            </div>
        @endif
    @endif
@endsection
