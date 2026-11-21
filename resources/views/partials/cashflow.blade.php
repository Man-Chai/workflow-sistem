@php
    $count = count($series['labels']);
    $width = 640;
    $height = 220;
    $padLeft = 54;
    $padRight = 14;
    $padTop = 16;
    $padBottom = 28;
    $chartWidth = $width - $padLeft - $padRight;
    $chartHeight = $height - $padTop - $padBottom;

    $max = (float) $series['max'];
    $min = (float) $series['min'];
    $range = max(1, $max - $min);

    // A little headroom makes the line feel like a market chart instead of touching the top.
    $max += $range * 0.12;
    $range = max(1, $max - $min);

    $x = fn (int $index): float => $padLeft + ($count > 1 ? $index * $chartWidth / ($count - 1) : $chartWidth / 2);
    $y = fn (float $value): float => $padTop + ($max - $value) / $range * $chartHeight;

    $line = function (array $values) use ($x, $y): string {
        $points = [];
        foreach ($values as $index => $value) {
            $points[] = number_format($x($index), 2, '.', '').','.number_format($y((float) $value), 2, '.', '');
        }

        return 'M '.implode(' L ', $points);
    };

    $balanceLine = $line($series['balance']);
    $incomeLine = $line($series['income']);
    $expenseLine = $line($series['expense']);
    $balanceArea = $balanceLine.' L '.number_format($x($count - 1), 2, '.', '').','.number_format($height - $padBottom, 2, '.', '').' L '.number_format($x(0), 2, '.', '').','.number_format($height - $padBottom, 2, '.', '').' Z';

    $lastBalance = (int) ($series['balance'][$count - 1] ?? 0);
    $change = (int) $series['change'];
    $changePercent = (float) $series['change_percent'];
    $isUp = $change >= 0;
@endphp

<div class="card">
    <div class="card-head">
        <div>
            <h3 class="card-title"><x-icon name="trending-up" :size="16" /> Tren kas 6 bulan</h3>
            <p class="card-sub">Pergerakan saldo kas seperti grafik saham, lengkap dengan pemasukan dan pengeluaran.</p>
        </div>
        <div class="legend">
            <span class="legend-item"><span class="legend-swatch"></span> Saldo</span>
            <span class="legend-item"><span class="legend-swatch is-in"></span> Masuk</span>
            <span class="legend-item"><span class="legend-swatch is-out"></span> Keluar</span>
        </div>
    </div>
    <div class="card-body">
        <div class="stock-chart-head">
            <div>
                <span class="stock-chart-label">Saldo akhir periode</span>
                <strong class="stock-chart-value">{{ rupiah($lastBalance) }}</strong>
            </div>
            <span class="stock-delta {{ $isUp ? 'is-up' : 'is-down' }}">
                <x-icon :name="$isUp ? 'trending-up' : 'arrow-down-left'" :size="13" />
                {{ $isUp ? '+' : '−' }}{{ rupiah(abs($change)) }}
                <small>({{ $isUp ? '+' : '−' }}{{ number_format(abs($changePercent), 1, ',', '.') }}%)</small>
            </span>
        </div>

        <div class="stock-chart">
            <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-label="Grafik tren kas enam bulan terakhir">
                <defs>
                    <linearGradient id="cashArea" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#10845b" stop-opacity="0.28" />
                        <stop offset="100%" stop-color="#10845b" stop-opacity="0" />
                    </linearGradient>
                </defs>

                @for($i = 0; $i <= 4; $i++)
                    @php
                        $gridValue = $max - ($range * $i / 4);
                        $gridY = $y($gridValue);
                    @endphp
                    <line class="stock-grid-line" x1="{{ $padLeft }}" y1="{{ number_format($gridY, 2, '.', '') }}" x2="{{ $width - $padRight }}" y2="{{ number_format($gridY, 2, '.', '') }}" />
                    <text class="stock-axis-label" x="{{ $padLeft - 10 }}" y="{{ number_format($gridY + 3, 2, '.', '') }}" text-anchor="end">{{ rupiah_short($gridValue) }}</text>
                @endfor

                <path class="stock-area" d="{{ $balanceArea }}" />
                <path class="stock-line is-in" d="{{ $incomeLine }}" />
                <path class="stock-line is-out" d="{{ $expenseLine }}" />
                <path class="stock-line" d="{{ $balanceLine }}" />

                @foreach($series['balance'] as $index => $value)
                    <circle class="stock-point {{ $value < 0 ? 'is-down' : '' }}" cx="{{ number_format($x($index), 2, '.', '') }}" cy="{{ number_format($y((float) $value), 2, '.', '') }}" r="3.4">
                        <title>{{ $series['labels'][$index] }} · Saldo {{ rupiah($value) }} · Masuk {{ rupiah($series['income'][$index]) }} · Keluar {{ rupiah($series['expense'][$index]) }}</title>
                    </circle>
                    <text class="stock-month-label" x="{{ number_format($x($index), 2, '.', '') }}" y="{{ $height - 6 }}" text-anchor="middle">{{ $series['labels'][$index] }}</text>
                @endforeach
            </svg>
        </div>

        <div class="divider"></div>
        <div class="progress-meta">
            <span>Total pemasukan 6 bulan</span>
            <strong class="text-in">{{ rupiah(array_sum($series['income'])) }}</strong>
        </div>
        <div class="progress-meta">
            <span>Total pengeluaran 6 bulan</span>
            <strong class="text-out">{{ rupiah(array_sum($series['expense'])) }}</strong>
        </div>
    </div>
</div>
