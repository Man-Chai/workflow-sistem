@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
    @endphp

    @if(! $class)
        <div class="page-head">
            <div>
                <span class="eyebrow"><x-icon name="school" :size="13" /> Koordinator kelas</span>
                <h1 class="page-title">{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
                <p class="page-sub">Akun Anda belum terhubung ke kelas mana pun.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <x-empty icon="school" title="Belum ada kelas yang dikoordinasikan" text="Hubungi administrator untuk menugaskan Anda sebagai koordinator kelas. Setelah ditugaskan, seluruh fitur kas, agenda, dan pemberitahuan kelas akan terbuka.">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm">Lihat profil</a>
                </x-empty>
            </div>
        </div>
    @else
        <div class="page-head">
            <div>
                <span class="eyebrow"><x-icon name="school" :size="13" /> {{ $class->name }} · {{ $class->academic_year }}</span>
                <h1 class="page-title">{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
                <p class="page-sub">Kelola kas, verifikasi iuran, dan jaga kelas tetap terinformasi. {{ now()->translatedFormat('l, d F Y') }}.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('cash.index') }}" class="btn btn-outline">
                    <x-icon name="wallet" :size="16" />
                    Buka kas
                </a>
                <a href="{{ route('announcements.create') }}" class="btn btn-primary">
                    <x-icon name="bell" :size="16" />
                    Buat pemberitahuan
                </a>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Saldo kas kelas</span>
                    <span class="stat-icon"><x-icon name="wallet" :size="17" /></span>
                </div>
                <div class="stat-value">{{ rupiah($balance) }}</div>
                <div class="stat-meta"><x-icon name="trending-up" :size="12" /> Saldo berjalan saat ini</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Siswa aktif</span>
                    <span class="stat-icon is-blue"><x-icon name="users" :size="17" /></span>
                </div>
                <div class="stat-value">{{ $class->students_count }}</div>
                <div class="stat-meta"><x-icon name="school" :size="12" /> {{ $class->name }}</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Iuran terkumpul</span>
                    <span class="stat-icon is-violet"><x-icon name="trending-up" :size="17" /></span>
                </div>
                <div class="stat-value">{{ $rate }}%</div>
                <div class="stat-meta">{{ rupiah($verified) }} dari {{ rupiah($target) }}</div>
            </div>

            <div class="stat">
                <div class="stat-top">
                    <span class="stat-label">Perlu verifikasi</span>
                    <span class="stat-icon is-amber"><x-icon name="clock" :size="17" /></span>
                </div>
                <div class="stat-value">{{ $pendingCount }}</div>
                <div class="stat-meta">
                    @if($pendingCount > 0)
                        <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']) }}">Tinjau sekarang <x-icon name="arrow-right" :size="11" /></a>
                    @else
                        Semua pembayaran tertangani
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-main">
            @include('partials.cashflow', ['series' => $series])

            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="wallet" :size="16" /> Progres iuran</h3>
                        <p class="card-sub">Perbandingan iuran terverifikasi dan target kelas.</p>
                    </div>
                    <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'iuran']) }}" class="card-link">Kelola</a>
                </div>
                <div class="card-body">
                    <div class="donut-wrap">
                        <div class="donut" style="--p: {{ $rate }}">
                            <div class="donut-center">
                                <strong>{{ $rate }}%</strong>
                                <span>terkumpul</span>
                            </div>
                        </div>
                        <div class="donut-legend">
                            <div class="legend-row">
                                <span><span class="legend-dot"></span> Terkumpul</span>
                                <strong>{{ rupiah($verified) }}</strong>
                            </div>
                            <div class="legend-row">
                                <span><span class="legend-dot is-muted"></span> Sisa target</span>
                                <strong>{{ rupiah(max(0, $target - $verified)) }}</strong>
                            </div>
                            <div class="legend-row">
                                <span>Total target</span>
                                <strong>{{ rupiah($target) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="due-list">
                        <x-empty icon="receipt" title="Pembukuan & Laporan Keuangan" text="Gunakan menu Kas Kelas untuk mencatat pembayaran siswa ke tabel pembukuan atau transaksi kas ke laporan_keuangan.">
                            <a href="{{ route('payments.create', ['class' => $class->id]) }}" class="btn btn-sm btn-primary">Catat pembukuan</a>
                        </x-empty>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="clock" :size="16" /> Perlu verifikasi</h3>
                        <p class="card-sub">{{ $pendingCount }} pembayaran menunggu keputusan Anda.</p>
                    </div>
                    <a href="{{ route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']) }}" class="card-link">Lihat semua</a>
                </div>
                <div class="card-body">
                    <div class="feed">
                        @forelse($pendingPayments as $payment)
                            <div class="feed-item">
                                <x-avatar :name="$payment->student->name" size="sm" />
                                <div class="feed-body">
                                    <span class="feed-title">{{ $payment->student->name }}</span>
                                    <div class="feed-meta">{{ $payment->due->title }}</div>
                                    <div class="feed-meta">{{ $payment->paid_at->format('d M Y') }} · {{ rupiah($payment->amount) }}</div>
                                </div>
                                <form method="POST" action="{{ route('payments.verify', $payment) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Verifikasi</button>
                                </form>
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
                        <p class="card-sub">Kegiatan terdekat kelas Anda.</p>
                    </div>
                    <a href="{{ route('agenda.index') }}" class="card-link">Lihat agenda</a>
                </div>
                <div class="card-body">
                    @include('partials.agenda-list', ['agendas' => $upcomingAgendas])
                </div>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="bell" :size="16" /> Pemberitahuan terbaru</h3>
                        <p class="card-sub">Kabar untuk kelas dan seluruh sekolah.</p>
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
                        <p class="card-sub">Pemasukan dan pengeluaran terbaru.</p>
                    </div>
                    <a href="{{ route('cash.index', ['class' => $class->id]) }}" class="card-link">Buku kas</a>
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table class="table table-compact">
                            <thead>
                                <tr>
                                    <th>Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="cell-main">{{ $transaction->category }}</span>
                                            <span class="cell-sub">{{ $transaction->description ? \Illuminate\Support\Str::limit($transaction->description, 34) : 'Tanpa keterangan' }}</span>
                                        </td>
                                        <td class="nowrap">{{ $transaction->transaction_date->format('d M Y') }}</td>
                                        <td>
                                            <span class="amount {{ $transaction->type === 'masuk' ? 'text-in' : 'text-out' }}">
                                                {{ $transaction->type === 'masuk' ? '+' : '−' }} {{ rupiah($transaction->amount) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">
                                            <x-empty icon="receipt" title="Belum ada transaksi" text="Catat transaksi pertama kelas Anda." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
