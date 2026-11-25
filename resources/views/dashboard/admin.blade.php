@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="shield" :size="13" /> Ringkasan sekolah</span>
            <h1 class="page-title">{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="page-sub">Pantau kelas, kas, dan aktivitas seluruh komunitas dari satu tempat. {{ now()->translatedFormat('l, d F Y') }}.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('announcements.create') }}" class="btn btn-outline">
                <x-icon name="bell" :size="16" />
                Buat pemberitahuan
            </a>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah pengguna
            </a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total kelas</span>
                <span class="stat-icon"><x-icon name="school" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $stats['classes'] }}</div>
            <div class="stat-meta"><x-icon name="users" :size="12" /> {{ $stats['students'] }} siswa terdaftar</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Data orang tua</span>
                <span class="stat-icon is-blue"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $stats['parents'] }}</div>
            <div class="stat-meta"><x-icon name="shield" :size="12" /> Terhubung di tabel ortu</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Saldo kas semua kelas</span>
                <span class="stat-icon is-violet"><x-icon name="wallet" :size="17" /></span>
            </div>
            <div class="stat-value">{{ rupiah($stats['balance']) }}</div>
            <div class="stat-meta"><x-icon name="trending-up" :size="12" /> Laporan keuangan kelas</div>
        </div>

        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Catatan pembukuan</span>
                <span class="stat-icon is-amber"><x-icon name="receipt" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-meta">
                <a href="{{ route('cash.index', ['tab' => 'pembayaran']) }}">Lihat pembukuan <x-icon name="arrow-right" :size="11" /></a>
            </div>
        </div>
    </div>

    <div class="grid grid-main">
        @include('partials.cashflow', ['series' => $series])

        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="school" :size="16" /> Kelas & koordinator</h3>
                    <p class="card-sub">Kesehatan kas dan tingkat kolektibilitas iuran.</p>
                </div>
                <a href="{{ route('classes.index') }}" class="card-link">Kelola <x-icon name="arrow-right" :size="12" /></a>
            </div>
            <div class="card-body">
                @forelse($classRows as $row)
                    <div class="agenda-row">
                        <div class="date-block" style="width: 42px;">
                            <strong style="font-size: 12px;">{{ $row['students'] }}</strong>
                            <span> siswa</span>
                        </div>
                        <div class="agenda-body">
                            <a href="{{ route('classes.show', $row['class']) }}" class="agenda-title">{{ $row['class']->name }}</a>
                            <div class="agenda-meta">
                                <span>{{ $row['class']->coordinator?->name ?? 'Belum ada koordinator' }}</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar {{ $row['rate'] < 50 ? 'is-warn' : '' }}" style="width: {{ $row['rate'] }}%"></div>
                            </div>
                            <div class="progress-meta">
                                <span>{{ $row['rate'] }}% iuran terkumpul</span>
                                <strong>{{ rupiah($row['balance']) }}</strong>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty icon="school" title="Belum ada kelas" text="Tambahkan kelas pertama untuk mulai mengelola komunitas.">
                        <a href="{{ route('classes.create') }}" class="btn btn-sm btn-primary">Tambah kelas</a>
                    </x-empty>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="clock" :size="16" /> Menunggu verifikasi</h3>
                    <p class="card-sub">{{ $stats['pending'] }} pembayaran perlu ditinjau.</p>
                </div>
                <a href="{{ route('cash.index', ['tab' => 'pembayaran']) }}" class="card-link">Lihat semua</a>
            </div>
            <div class="card-body">
                <div class="feed">
                    @forelse($pendingPayments as $payment)
                        <div class="feed-item">
                            <x-avatar :name="$payment->student->name" size="sm" />
                            <div class="feed-body">
                                <span class="feed-title">{{ $payment->student->name }}</span>
                                <div class="feed-meta">{{ $payment->due->title }} · {{ $payment->due->classRoom?->name }}</div>
                                <div class="feed-meta">{{ $payment->paid_at->format('d M Y') }} · {{ ucfirst($payment->method) }}</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="amount small">{{ rupiah($payment->amount) }}</div>
                                <form method="POST" action="{{ route('payments.verify', $payment) }}" class="mt-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Verifikasi</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <x-empty icon="circle-check" title="Tidak ada antrean" text="Semua pembayaran sudah diverifikasi." />
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="calendar" :size="16" /> Agenda mendatang</h3>
                    <p class="card-sub">Kegiatan terdekat di seluruh kelas.</p>
                </div>
                <a href="{{ route('agenda.index') }}" class="card-link">Lihat agenda</a>
            </div>
            <div class="card-body">
                @include('partials.agenda-list', ['agendas' => $agendas])
            </div>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="bell" :size="16" /> Pemberitahuan terbaru</h3>
                    <p class="card-sub">Kabar yang baru terbit untuk komunitas.</p>
                </div>
                <a href="{{ route('announcements.index') }}" class="card-link">Semua kabar</a>
            </div>
            <div class="card-body">
                @include('partials.announcement-feed', ['announcements' => $announcements])
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="receipt" :size="16" /> Transaksi terakhir</h3>
                    <p class="card-sub">Arus kas terbaru dari seluruh kelas.</p>
                </div>
                <a href="{{ route('cash.index') }}" class="card-link">Buku kas</a>
            </div>
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table table-compact">
                        <thead>
                            <tr>
                                <th>Transaksi</th>
                                <th>Kelas</th>
                                <th>Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>
                                        <span class="cell-main">{{ $transaction->category }}</span>
                                        <span class="cell-sub">{{ $transaction->transaction_date->format('d M Y') }}</span>
                                    </td>
                                    <td><span class="badge badge-neutral">{{ $transaction->classRoom?->name }}</span></td>
                                    <td>
                                        <span class="amount {{ $transaction->type === 'masuk' ? 'text-in' : 'text-out' }}">
                                            {{ $transaction->type === 'masuk' ? '+' : '−' }} {{ rupiah($transaction->amount) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <x-empty icon="receipt" title="Belum ada transaksi" text="Transaksi kas akan tampil di sini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
