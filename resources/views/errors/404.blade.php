<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman tidak ditemukan · KorlasSync</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="error-body">
    <div class="error-card">
        <a href="{{ url('/') }}" class="error-brand">
            <span class="brand-mark">K</span>
            KorlasSync
        </a>
        <div class="error-code">404</div>
        <h1>Halaman tidak ditemukan</h1>
        <p>Halaman yang Anda cari mungkin sudah dipindahkan atau tautannya tidak lagi berlaku. Kembali ke dashboard untuk melanjutkan.</p>
        <div class="error-actions">
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary">
                <x-icon name="grid" :size="15" />
                {{ auth()->check() ? 'Kembali ke dashboard' : 'Masuk ke akun' }}
            </a>
        </div>
    </div>
</body>
</html>
