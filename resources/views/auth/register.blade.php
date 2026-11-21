<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071913">
    <title>Daftar Akun · KorlasSync</title>
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
            <span class="eyebrow"><x-icon name="sparkles" :size="13" /> Akun orang tua</span>
            <h1>Daftar sekali, pantau kabar kelas <em>tanpa ribet</em>.</h1>
            <p>Buat akun orang tua untuk menerima agenda, pemberitahuan, dan informasi kelas anak Anda. Pengelolaan kas kelas tetap dipegang koordinator kelas agar pencatatan lebih terkontrol.</p>

            <ul class="auth-features">
                <li>
                    <span class="auth-feature-icon"><x-icon name="bell" :size="14" /></span>
                    <span><strong>Kabar kelas.</strong> Pemberitahuan penting dari koordinator langsung tampil di dashboard.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="calendar" :size="14" /></span>
                    <span><strong>Agenda terkoordinasi.</strong> Rapat, kerja bakti, dan kegiatan kelas selalu dalam pantauan.</span>
                </li>
                <li>
                    <span class="auth-feature-icon"><x-icon name="users" :size="14" /></span>
                    <span><strong>Data anak terhubung.</strong> Hubungi admin sekolah agar akun Anda terhubung ke data siswa.</span>
                </li>
            </ul>

            <div class="auth-showcase">
                <div class="showcase-card">
                    <span>Pendaftaran</span>
                    <strong>Gratis</strong>
                    <small>Khusus akun orang tua</small>
                </div>
                <div class="showcase-card">
                    <span>Kas kelas</span>
                    <strong>1 pengelola</strong>
                    <small>Dipegang koordinator kelas</small>
                </div>
                <div class="showcase-card">
                    <span>Informasi</span>
                    <strong>1 ruang</strong>
                    <small>Agenda dan pemberitahuan</small>
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

        <span class="eyebrow">Daftar akun</span>
        <h2>Buat akun orang tua</h2>
        <p class="lead">Isi data berikut. Akun koordinator dan admin dibuat oleh pihak sekolah.</p>

        @include('partials.flash')

        <form method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf

            <div class="field">
                <label class="label" for="name">Nama lengkap <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="name"
                        class="input"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Nama sesuai data sekolah"
                        autocomplete="name"
                        required
                        autofocus
                        maxlength="120"
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="user" :size="16" />
                    </span>
                </div>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>

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
                        maxlength="150"
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="mail" :size="16" />
                    </span>
                </div>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="label" for="phone">Nomor telepon</label>
                <div class="input-group">
                    <input
                        id="phone"
                        class="input"
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        placeholder="08xx xxxx xxxx"
                        autocomplete="tel"
                        maxlength="30"
                        style="padding-left: 37px;"
                    >
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted-2);pointer-events:none;">
                        <x-icon name="phone" :size="16" />
                    </span>
                </div>
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="label" for="password">Kata sandi <span class="req">*</span></label>
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
                <label class="label" for="password_confirmation">Ulangi kata sandi <span class="req">*</span></label>
                <div class="input-group">
                    <input
                        id="password_confirmation"
                        class="input"
                        type="password"
                        name="password_confirmation"
                        placeholder="Ketik ulang kata sandi"
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
                Buat akun orang tua
                <x-icon name="arrow-right" :size="16" />
            </button>
        </form>

        <p class="auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>

        <p class="auth-legal">Dengan mendaftar, Anda menyetujui penggunaan data untuk keperluan administrasi kelas.<br>© {{ now()->year }} KorlasSync. Dibuat untuk komunitas kelas.</p>
    </div>
</section>
</body>
</html>
