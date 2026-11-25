<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071913">
    <title>Masuk · KorlasSync</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script defer src="{{ asset('assets/js/app.js') }}"></script>
</head>
<body class="auth-body">
<main class="auth-visual">
    <div class="auth-visual-inner">
        <a href="{{ url('/') }}" class="auth-brand">
            <span class="brand-mark">K</span>
            <span class="brand-text">
                <span class="brand-name">KorlasSync</span>
                <span class="brand-tag">Ruang kelas terpadu</span>
            </span>
        </a>

        <div class="auth-copy">
            <span class="eyebrow"><x-icon name="sparkles" :size="13" /> Kolaborasi kelas, tanpa ribet</span>
            <h1>Kelola kelas, kas, dan kabar sekolah dalam <em>satu ruang</em>.</h1>
            <p>KorlasSync menyatukan koordinator kelas, orang tua, dan sekolah. Setiap pembukuan tercatat, setiap agenda terlihat, dan setiap kabar sampai ke orang yang tepat.</p>

            <ul class="auth-features">
                <li>
                    <span class="auth-feature-icon"><x-icon name="wallet" :size="14" /></span>
                    <span><strong>Kas yang transparan.</strong> Catat pemasukan, pengeluaran, dan pembukuan siswa dengan riwayat yang rapi.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="calendar" :size="14" /></span>
                    <span><strong>Agenda yang terkoordinasi.</strong> Rapat, kegiatan, dan jadwal kelas terintegrasi dengan pengingat grup WhatsApp.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="bell" :size="14" /></span>
                    <span><strong>Kabar yang sampai.</strong> Pemberitahuan penting langsung tampil di dashboard sesuai kelas Anda.</span>
                </li>
            </ul>

            <div class="auth-showcase">
                <div class="showcase-card">
                    <span>Peran terhubung</span>
                    <strong>3 peran</strong>
                    <small>Admin · Korlas · User</small>
                </div>
                <div class="showcase-card">
                    <span>Transparansi kas</span>
                    <strong>100%</strong>
                    <small>Setiap rupiah tercatat</small>
                </div>
                <div class="showcase-card">
                    <span>Ruang terpadu</span>
                    <strong>1 ruang</strong>
                    <small>Kas, agenda, WhatsApp Bot</small>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-foot">Dibuat untuk kelas yang lebih tertata · KorlasSync {{ now()->year }}</div>
</main>

<section class="auth-panel">
    <div class="auth-form">
        <a href="{{ url('/') }}" class="auth-mobile-brand">
            <span class="brand-mark">K</span>
            <span class="brand-text">
                <span class="brand-name">KorlasSync</span>
                <span class="brand-tag">Ruang kelas terpadu</span>
            </span>
        </a>

        <span class="eyebrow">Masuk akun</span>
        <h2>Selamat datang kembali</h2>
        <p class="lead">Gunakan alamat email yang terdaftar pada sistem KorlasSync.</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf

            <div class="field">
                <label class="label" for="email">Alamat email <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="email"
                        class="input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        autocomplete="email"
                        required
                        autofocus
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="mail" :size="16" />
                    </span>
                </div>
            </div>

            <div class="field">
                <label class="label" for="password">Kata sandi <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="password"
                        class="input"
                        type="password"
                        name="password"
                        placeholder="Masukkan kata sandi"
                        autocomplete="current-password"
                        required
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="lock" :size="16" />
                    </span>
                    <button type="button" class="input-action" data-password-toggle="password" aria-label="Tampilkan kata sandi">
                        <x-icon name="eye" :size="16" class="pw-eye" />
                        <x-icon name="eye-off" :size="16" class="pw-eye-off" />
                    </button>
                </div>
            </div>

            <div class="auth-row">
                <label class="checkbox">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Ingat saya di perangkat ini</span>
                </label>
                <a href="{{ route('password.request') }}" class="auth-help" style="color: var(--brand); font-weight: 600; text-decoration: none;">Lupa kata sandi?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Masuk ke dashboard
                <x-icon name="arrow-right" :size="16" />
            </button>
        </form>

        <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai orang tua</a></p>

        <p class="auth-legal">Dengan masuk, Anda menyetujui penggunaan data untuk keperluan administrasi kelas.<br>© {{ now()->year }} KorlasSync. Dibuat untuk komunitas kelas.</p>
    </div>
</section>
</body>
</html>
