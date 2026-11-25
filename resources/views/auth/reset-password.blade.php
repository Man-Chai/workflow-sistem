<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071913">
    <title>Buat Kata Sandi Baru · KorlasSync</title>
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
            <span class="eyebrow"><x-icon name="shield" :size="13" /> Tautan terverifikasi</span>
            <h1>Buat kata sandi baru yang <em>kuat dan aman</em>.</h1>
            <p>Tautan verifikasi untuk akun <strong>{{ $user->name }}</strong> ({{ $user->email }}) telah tervalidasi. Silakan masukkan kata sandi baru Anda.</p>

            <ul class="auth-features">
                <li>
                    <span class="auth-feature-icon"><x-icon name="lock" :size="14" /></span>
                    <span><strong>Minimal 8 Karakter.</strong> Kombinasikan huruf besar, huruf kecil, dan angka.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="shield" :size="14" /></span>
                    <span><strong>Tersimpan Terenkripsi.</strong> Kata sandi langsung di-hash dengan Bcrypt sebelum disimpan ke PostgreSQL.</span>
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

        <span class="eyebrow">Atur ulang kata sandi</span>
        <h2>Kata sandi baru</h2>
        <p class="lead">Atur kata sandi baru untuk akun <strong>{{ $user->name }}</strong>.</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="field">
                <label class="label">Akun terverifikasi</label>
                <input type="text" class="input" value="{{ $user->name }} · {{ $email }}" disabled>
            </div>

            <div class="field">
                <label class="label" for="password">Kata sandi baru <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="password"
                        class="input"
                        type="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        autocomplete="new-password"
                        required
                        minlength="8"
                        autofocus
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
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="label" for="password_confirmation">Konfirmasi kata sandi baru <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="password_confirmation"
                        class="input"
                        type="password"
                        name="password_confirmation"
                        placeholder="Ketik ulang kata sandi baru"
                        autocomplete="new-password"
                        required
                        minlength="8"
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="lock" :size="16" />
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Simpan kata sandi baru
                <x-icon name="check" :size="16" />
            </button>
        </form>

        <p class="auth-switch"><a href="{{ route('login') }}">Batal dan kembali ke halaman masuk</a></p>
    </div>
</section>
</body>
</html>
