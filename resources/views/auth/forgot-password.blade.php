<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071913">
    <title>Lupa Kata Sandi · KorlasSync</title>
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
            <span class="eyebrow"><x-icon name="lock" :size="13" /> Pemulihan keamanan akun</span>
            <h1>Atur ulang kata sandi melalui <em>tautan verifikasi</em>.</h1>
            <p>Demi keamanan, pengaturan ulang kata sandi hanya dapat dilakukan melalui tautan verifikasi unik yang dikirim ke alamat email atau nomor telepon WhatsApp yang terdaftar pada akun Anda.</p>

            <ul class="auth-features">
                <li>
                    <span class="auth-feature-icon"><x-icon name="mail" :size="14" /></span>
                    <span><strong>Via Email atau Nomor Telepon.</strong> Masukkan email atau nomor telepon/WhatsApp yang terdaftar di tabel pengguna.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="shield" :size="14" /></span>
                    <span><strong>Enkripsi Bcrypt.</strong> Kata sandi Anda dienkripsi penuh menggunakan algoritma Bcrypt satu arah.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="clock" :size="14" /></span>
                    <span><strong>Tautan Sekali Pakai.</strong> Tautan verifikasi berlaku selama 60 menit dan otomatis hangus setelah digunakan.</span>
                </li>
            </ul>
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

        <span class="eyebrow">Verifikasi akun</span>
        <h2>Reset kata sandi</h2>
        <p class="lead">Masukkan alamat email atau nomor telepon/WhatsApp Anda yang terdaftar untuk menerima tautan verifikasi pengaturan ulang kata sandi.</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('password.email') }}" novalidate>
            @csrf

            <div class="field">
                <label class="label" for="identifier">Email atau Nomor Telepon <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="identifier"
                        class="input"
                        type="text"
                        name="identifier"
                        value="{{ old('identifier', auth()->user()?->email) }}"
                        placeholder="nama@email.com atau 628xxxx"
                        required
                        autofocus
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="mail" :size="16" />
                    </span>
                </div>
                <span class="hint">Tautan verifikasi akan dikirim ke WhatsApp dan/atau email akun yang cocok.</span>
                @error('identifier') <span class="error">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Kirim tautan verifikasi
                <x-icon name="arrow-right" :size="16" />
            </button>
        </form>

        <p class="auth-switch">
            @auth
                <a href="{{ route('profile.edit') }}">Kembali ke profil</a>
            @else
                Sudah ingat kata sandi? <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
            @endauth
        </p>

        <p class="auth-legal">© {{ now()->year }} KorlasSync. Keamanan akun dilindungi enkripsi Bcrypt.</p>
    </div>
</section>
</body>
</html>
