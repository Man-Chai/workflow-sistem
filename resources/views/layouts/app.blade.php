@php
    $user = auth()->user();
    $sideClass = $user->isCoordinator() ? $user->classesCoordinated()->first() : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#071913">
    <title>@yield('title', 'Dashboard') · KorlasSync</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script defer src="{{ asset('assets/js/app.js') }}"></script>
</head>
<body class="app-body">
<a class="skip-link" href="#main">Lewati ke konten</a>

<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-mark">K</span>
            <span class="brand-text">
                <span class="brand-name">KorlasSync</span>
                <span class="brand-tag">Ruang kelas terpadu</span>
            </span>
        </a>

        <nav class="side-nav">
            <span class="side-label">Utama</span>
            <a href="{{ route('dashboard') }}" class="side-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                <x-icon name="grid" :size="18" />
                <span>Dashboard</span>
            </a>

            <span class="side-label">Ruang kelas</span>
            @if(! $user->isParent())
                <a href="{{ route('cash.index') }}" class="side-link {{ request()->routeIs('cash.*') || request()->routeIs('dues.*') || request()->routeIs('payments.*') ? 'is-active' : '' }}">
                    <x-icon name="wallet" :size="18" />
                    <span>Kas kelas</span>
                </a>
            @endif
            <a href="{{ route('agenda.index') }}" class="side-link {{ request()->routeIs('agenda.*') ? 'is-active' : '' }}">
                <x-icon name="calendar" :size="18" />
                <span>Agenda</span>
            </a>
            <a href="{{ route('announcements.index') }}" class="side-link {{ request()->routeIs('announcements.*') ? 'is-active' : '' }}">
                <x-icon name="bell" :size="18" />
                <span>Pemberitahuan</span>
                @if(($notificationCount ?? 0) > 0)
                    <span class="side-badge">{{ $notificationCount }}</span>
                @endif
            </a>

            @if($user->isAdmin())
                <span class="side-label">Administrasi</span>
                <a href="{{ route('classes.index') }}" class="side-link {{ request()->routeIs('classes.*') ? 'is-active' : '' }}">
                    <x-icon name="school" :size="18" />
                    <span>Kelas & siswa</span>
                </a>
                <a href="{{ route('users.index') }}" class="side-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">
                    <x-icon name="users" :size="18" />
                    <span>Pengguna</span>
                </a>
            @endif

            @if($sideClass)
                <span class="side-label">Kelas saya</span>
                <a href="{{ route('classes.show', $sideClass) }}" class="side-class-card {{ request()->routeIs('classes.show') ? 'is-active' : '' }}">
                    <span class="side-class-icon"><x-icon name="school" :size="17" /></span>
                    <span class="side-class-meta">
                        <strong>{{ $sideClass->name }}</strong>
                        <small>{{ $sideClass->academic_year }}</small>
                    </span>
                    <x-icon name="chevron-right" :size="15" />
                </a>
            @endif
        </nav>

        <div class="side-foot">
            <a href="{{ route('profile.edit') }}" class="side-user {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                <x-avatar :name="$user->name" size="sm" />
                <span class="side-user-meta">
                    <strong>{{ $user->name }}</strong>
                    <small>{{ $user->roleLabel() }}</small>
                </span>
                <x-icon name="settings" :size="16" />
            </a>
            <form method="POST" action="{{ route('logout') }}" class="side-logout">
                @csrf
                <button type="submit">
                    <x-icon name="logout" :size="16" />
                    <span>Keluar akun</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-close></div>

    <div class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn menu-btn" data-sidebar-toggle aria-label="Buka menu navigasi">
                    <x-icon name="menu" :size="19" />
                </button>
                <div class="crumbs">
                    <span>Ruang kerja</span>
                    <span class="crumb-sep">/</span>
                    <strong>@yield('title', 'Dashboard')</strong>
                </div>
            </div>
            <div class="topbar-right">
                <span class="date-chip">
                    <x-icon name="calendar" :size="15" />
                    {{ now()->translatedFormat('d M Y') }}
                </span>
                <a href="{{ route('announcements.index') }}" class="icon-btn {{ ($notificationCount ?? 0) > 0 ? 'has-dot' : '' }}" aria-label="Pemberitahuan terbaru">
                    <x-icon name="bell" :size="18" />
                    @if(($notificationCount ?? 0) > 0)
                        <span class="dot">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.edit') }}" class="user-chip">
                    <x-avatar :name="$user->name" size="sm" />
                    <span class="user-chip-meta">
                        <strong>{{ $user->name }}</strong>
                        <small>{{ $user->roleLabel() }}</small>
                    </span>
                </a>
            </div>
        </header>

        <main id="main" class="page">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="app-footer">
            <span>KorlasSync © {{ now()->year }} · Ruang kolaborasi kelas</span>
            <span class="footer-dot"></span>
            <span>Transparan, tertata, terhubung.</span>
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
