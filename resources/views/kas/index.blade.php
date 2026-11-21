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
            <h1 class="page-title">Kas kelas</h1>
            <p class="page-sub">
                @if($class)
                    Buku kas, iuran, dan verifikasi pembayaran {{ $class->name }} dalam satu halaman.
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
                <a href="{{ route('dues.create') }}" class="btn btn-outline">
                    <x-icon name="receipt" :size="16" />
                    Buat iuran
                </a>
                <a href="{{ route('cash.create') }}" class="btn btn-primary">
                    <x-icon name="plus" :size="16" />
                    Catat transaksi
                </a>
            </div>
        @endif
    </div>

    @if(! $class)
        <div class="card">
            <div class="card-body">
                <x-empty icon="wallet" title="Belum ada kelas terhubung" text="Akun Anda belum terhubung ke kelas mana pun. Hubungi administrator untuk menghubungkan akun dengan data siswa atau penugasan koordinator.">
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
                        <option value="{{ $classOption->id }}" @selected($classOption->id === $class->id)>
                            {{ $classOption->name }} · {{ $classOption->academic_year }}
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
                <p>Saldo berjalan dari seluruh transaksi terverifikasi.</p>
                <div class="hero-row">
                    <div class="hero-stat">
                        <span>Total masuk</span>
                        <strong>{{ rupiah($income) }}</strong>
                    </div>
                    <div class="hero-stat">
                        <span>Total keluar</span>
                        <strong>{{ rupiah($expense) }}</strong>
                    </div>
                    <div class="hero-stat">
                        <span>Tunggakan</span>
                        <strong>{{ rupiah($outstanding) }}</strong>
                    </div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Pemasukan {{ $monthLabel }}</span>
                    <span class="stat-icon"><x-icon name="arrow-down-left" :size="17" /></span>
                </div>
                <div class="stat-value text-in">{{ rupiah($monthIncome) }}</div>
                <div class="stat-meta">Kas masuk pada periode ini</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Pengeluaran {{ $monthLabel }}</span>
                    <span class="stat-icon is-rose"><x-icon name="receipt" :size="17" /></span>
                </div>
                <div class="stat-value text-out">{{ rupiah($monthExpense) }}</div>
                <div class="stat-meta">Kas keluar pada periode ini</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Perlu verifikasi</span>
                    <span class="stat-icon is-amber"><x-icon name="clock" :size="17" /></span>
                </div>
                <div class="stat-value">{{ $pendingCount }}</div>
                <div class="stat-meta">
                    @if($pendingCount > 0)
                        <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']) }}">Tinjau pembayaran <x-icon name="arrow-right" :size="11" /></a>
                    @else
                        Semua tertangani
                    @endif
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="tabs">
                <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'buku', 'month' => $month]) }}" class="tab {{ $tab === 'buku' ? 'is-active' : '' }}">
                    <x-icon name="receipt" :size="14" /> Buku kas
                </a>
                <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'iuran']) }}" class="tab {{ $tab === 'iuran' ? 'is-active' : '' }}">
                    <x-icon name="wallet" :size="14" /> Iuran
                    <span class="tab-count">{{ $dueRows->count() }}</span>
                </a>
                <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']) }}" class="tab {{ $tab === 'pembayaran' ? 'is-active' : '' }}">
                    <x-icon name="clock" :size="14" /> Pembayaran
                    @if($pendingCount > 0)
                        <span class="tab-count">{{ $pendingCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        @if($tab === 'buku')
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="receipt" :size="16" /> Buku kas</h3>
                        <p class="card-sub">Riwayat pemasukan dan pengeluaran {{ $periodLabel ?? $monthLabel }}.</p>
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
                                    <th>Transaksi</th>
                                    <th>Kategori</th>
                                    <th>Dicatat oleh</th>
                                    <th>Nominal</th>
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
                                            <span class="cell-main">{{ $transaction->description ? \Illuminate\Support\Str::limit($transaction->description, 46) : ucfirst($transaction->type) }}</span>
                                            <span class="cell-sub">
                                                {{ $transaction->payment_id ? 'Otomatis dari iuran' : 'Dicatat manual' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $transaction->type === 'masuk' ? 'badge-success' : 'badge-danger' }}">
                                                {{ $transaction->category }}
                                            </span>
                                        </td>
                                        <td class="muted small">{{ $transaction->recorder?->name ?? 'Sistem' }}</td>
                                        <td>
                                            <span class="amount {{ $transaction->type === 'masuk' ? 'text-in' : 'text-out' }}">
                                                {{ $transaction->type === 'masuk' ? '+' : '−' }} {{ rupiah($transaction->amount) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->payment_id === null)
                                                <form method="POST" action="{{ route('cash.destroy', $transaction) }}" data-confirm="Hapus transaksi ini? Tindakan ini tidak dapat dibatalkan.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" aria-label="Hapus transaksi" style="width: 29px; height: 29px;">
                                                        <x-icon name="trash" :size="14" />
                                                    </button>
                                                </form>
                                            @else
                                                <span class="tiny muted">Terkunci</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-empty icon="receipt" title="Belum ada transaksi" text="Transaksi yang dicatat pada periode ini akan tampil di sini.">
                                                <a href="{{ route('cash.create') }}" class="btn btn-sm btn-primary">Catat transaksi</a>
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

        @if($tab === 'iuran')
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="wallet" :size="16" /> Daftar iuran kelas</h3>
                        <p class="card-sub">Target iuran dihitung dari nominal per siswa dikalikan jumlah siswa aktif.</p>
                    </div>
                    <a href="{{ route('dues.create') }}" class="btn btn-sm btn-primary">
                        <x-icon name="plus" :size="13" /> Iuran baru
                    </a>
                </div>
                <div class="card-body">
                    <div class="due-list">
                        @forelse($dueRows as $row)
                            <div class="due-card">
                                <div class="due-card-head">
                                    <div>
                                        <h3>{{ $row['due']->title }}</h3>
                                        <div class="due-meta">
                                            <span>{{ $row['due']->period ?: 'Tanpa periode' }}</span>
                                            <span>·</span>
                                            <span>Jatuh tempo {{ $row['due']->due_date->format('d M Y') }}</span>
                                            <span>·</span>
                                            <span>{{ $row['due']->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </div>
                                    </div>
                                    <span class="due-amount">{{ rupiah($row['due']->amount) }}<span class="tiny muted"> /siswa</span></span>
                                </div>

                                <div class="due-progress">
                                    <div class="progress">
                                        <div class="progress-bar {{ $row['percent'] < 50 ? 'is-warn' : '' }}" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <div class="progress-meta">
                                        <span>{{ $row['percent'] }}% · {{ rupiah($row['verified']) }}</span>
                                        <span>Target {{ rupiah($row['target']) }}</span>
                                    </div>
                                </div>

                                <div class="due-actions">
                                    <span class="badge {{ $row['due']->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $row['due']->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    <a href="{{ route('payments.create', ['due' => $row['due']->id]) }}" class="btn btn-sm btn-primary">
                                        <x-icon name="wallet" :size="13" /> Catat pembayaran
                                    </a>
                                    <a href="{{ route('dues.edit', $row['due']) }}" class="btn btn-sm btn-outline">
                                        <x-icon name="edit" :size="13" /> Ubah
                                    </a>
                                    <form method="POST" action="{{ route('dues.destroy', $row['due']) }}" data-confirm="Hapus iuran ini beserta seluruh pembayarannya? Saldo kas yang sudah tercatat akan ikut berkurang.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <x-icon name="trash" :size="13" /> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <x-empty icon="circle-check" title="Belum ada iuran" text="Buat iuran pertama agar koordinator dapat mencatat pembayaran siswa.">
                                <a href="{{ route('dues.create') }}" class="btn btn-sm btn-primary">Buat iuran</a>
                            </x-empty>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($tab === 'pembayaran')
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="clock" :size="16" /> Pembayaran tercatat</h3>
                        <p class="card-sub">Pembayaran iuran yang dicatat koordinator dan langsung masuk ke kas kelas.</p>
                    </div>
                    @if($pendingCount > 0)
                        <x-badge tone="warning">{{ $pendingCount }} menunggu</x-badge>
                    @endif
                </div>
                <div class="card-body">
                    @if($pendingCount > 0)
                        <div class="notice mb-2">
                            <x-icon name="info" :size="15" />
                            <span>Masih ada pembayaran lama yang menunggu verifikasi. Verifikasi akan otomatis membuat transaksi pemasukan kas.</span>
                        </div>
                    @endif

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Siswa</th>
                                    <th>Iuran</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <x-avatar :name="$payment->student->name" size="sm" />
                                                <span>
                                                    <span class="cell-main">{{ $payment->student->name }}</span>
                                                    <span class="cell-sub">{{ $payment->student->nis }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cell-main">{{ $payment->due->title }}</span>
                                            <span class="cell-sub">{{ $payment->due->period ?: $payment->due->classRoom?->name }}</span>
                                        </td>
                                        <td class="nowrap">{{ $payment->paid_at->format('d M Y') }}</td>
                                        <td><span class="badge badge-neutral">{{ ucfirst($payment->method) }}</span></td>
                                        <td><span class="amount">{{ rupiah($payment->amount) }}</span></td>
                                        <td>
                                            <x-payment-status :payment="$payment" />
                                            @if($payment->note && $payment->status === 'rejected')
                                                <span class="cell-sub">{{ \Illuminate\Support\Str::limit($payment->note, 40) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                @if($payment->proof_path || $payment->proof_data)
                                                    <a href="{{ route('payments.proof', $payment) }}" target="_blank" rel="noopener" class="btn btn-sm btn-ghost">Bukti</a>
                                                @endif

                                                @if($payment->status === 'pending')
                                                    <form method="POST" action="{{ route('payments.verify', $payment) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary">Verifikasi</button>
                                                    </form>
                                                    <details class="inline-details">
                                                        <summary class="btn btn-sm btn-danger">Tolak</summary>
                                                        <form method="POST" action="{{ route('payments.reject', $payment) }}" class="reject-form">
                                                            @csrf
                                                            <input type="text" name="note" class="input" placeholder="Alasan penolakan" required maxlength="500">
                                                            <button type="submit" class="btn btn-sm btn-danger">Kirim</button>
                                                        </form>
                                                    </details>
                                                @endif

                                                <form method="POST" action="{{ route('payments.destroy', $payment) }}" data-confirm="Hapus pembayaran {{ $payment->student->name }}? Jika sudah terverifikasi, transaksi kas terkait ikut dihapus.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-ghost">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <x-empty icon="receipt" title="Belum ada pembayaran" text="Pembayaran iuran yang dicatat koordinator akan tampil di sini." />
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
