@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="settings" :size="13" /> Akun & keamanan</span>
            <h1 class="page-title">Profil saya</h1>
            <p class="page-sub">Perbarui informasi kontak dan kata sandi akun KorlasSync Anda.</p>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="user" :size="16" /> Informasi profil</h3>
                        <p class="card-sub">Nama dan kontak yang digunakan di komunitas kelas.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-grid">
                            <div class="field field-full">
                                <label class="label" for="name">Nama lengkap <span class="req">*</span></label>
                                <input type="text" name="name" id="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="120">
                                @error('name') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="label" for="email">Email <span class="req">*</span></label>
                                <input type="email" name="email" id="email" class="input" value="{{ old('email', $user->email) }}" required maxlength="150">
                                @error('email') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field">
                                <label class="label" for="phone">Nomor telepon</label>
                                <input type="text" name="phone" id="phone" class="input" value="{{ old('phone', $user->phone) }}" maxlength="30" placeholder="08xx xxxx xxxx">
                                @error('phone') <span class="error">{{ $message }}</span> @enderror
                            </div>

                            <div class="field field-full">
                                <label class="label">Peran akun</label>
                                <input type="text" class="input" value="{{ $user->roleLabel() }}" disabled>
                                <span class="hint">Peran hanya dapat diubah oleh administrator.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <x-icon name="check" :size="15" />
                                Simpan profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="lock" :size="16" /> Keamanan akun</h3>
                        <p class="card-sub">Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain.</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="field">
                            <label class="label" for="current_password">Kata sandi saat ini <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" class="input" required autocomplete="current-password">
                                <button type="button" class="input-action" data-password-toggle="current_password" aria-label="Tampilkan kata sandi">
                                    <x-icon name="eye" :size="15" class="pw-eye" />
                                    <x-icon name="eye-off" :size="15" class="pw-eye-off" />
                                </button>
                            </div>
                            @error('current_password') <span class="error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label class="label" for="password">Kata sandi baru <span class="req">*</span></label>
                                <input type="password" name="password" id="password" class="input" required minlength="8" autocomplete="new-password">
                                @error('password') <span class="error">{{ $message }}</span> @enderror
                            </div>
                            <div class="field">
                                <label class="label" for="password_confirmation">Ulangi kata sandi <span class="req">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="input" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-outline">
                                <x-icon name="lock" :size="15" />
                                Ganti kata sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 4px;">
                    <x-avatar :name="$user->name" size="xl" />
                    <h2 class="page-title" style="font-size: 18px; margin-top: 8px;">{{ $user->name }}</h2>
                    <span class="badge {{ $user->isAdmin() ? 'badge-violet' : ($user->isCoordinator() ? 'badge-brand' : 'badge-info') }}">{{ $user->roleLabel() }}</span>
                    <span class="tiny muted">{{ $user->email }}</span>
                </div>

                <div class="divider"></div>

                <div class="info-grid">
                    <div class="info-item">
                        <span>Status akun</span>
                        <strong>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</strong>
                    </div>
                    <div class="info-item">
                        <span>Bergabung</span>
                        <strong>{{ $user->created_at?->translatedFormat('d M Y') }}</strong>
                    </div>
                    <div class="info-item is-full">
                        <span>Telepon</span>
                        <strong>{{ $user->phone ?: 'Belum diisi' }}</strong>
                    </div>
                    <div class="info-item is-full">
                        <span>Terakhir masuk</span>
                        <strong>{{ $user->last_login_at?->translatedFormat('d M Y · H:i') ?? 'Belum pernah' }}</strong>
                    </div>
                </div>

                @if($user->isCoordinator())
                    <div class="divider"></div>
                    <span class="label">Kelas yang dikoordinasikan</span>
                    <div class="due-list mt-2">
                        @forelse($user->classesCoordinated as $class)
                            <a href="{{ route('classes.show', $class) }}" class="quick-link" style="text-decoration: none;">
                                <x-icon name="school" :size="15" />
                                {{ $class->name }}
                                <span class="tiny muted" style="margin-left: auto;">{{ $class->academic_year }}</span>
                            </a>
                        @empty
                            <span class="tiny muted">Belum ada penugasan kelas.</span>
                        @endforelse
                    </div>
                @endif

                @if($user->isParent())
                    <div class="divider"></div>
                    <span class="label">Anak terhubung</span>
                    <div class="mt-2">
                        @forelse($user->children as $child)
                            <div class="child-card">
                                <x-avatar :name="$child->name" size="sm" />
                                <div class="child-meta">
                                    <strong>{{ $child->name }}</strong>
                                    <span>{{ $child->classRoom?->name }} · NIS {{ $child->nis }}</span>
                                </div>
                            </div>
                        @empty
                            <span class="tiny muted">Belum ada anak yang terhubung dengan akun ini.</span>
                        @endforelse
                    </div>
                @endif

                <div class="notice mt-3">
                    <x-icon name="shield" :size="15" />
                    <span>Jangan bagikan kata sandi kepada siapa pun. Hubungi administrator jika Anda mencurigai aktivitas yang tidak dikenal.</span>
                </div>
            </div>
        </div>
    </div>
@endsection
