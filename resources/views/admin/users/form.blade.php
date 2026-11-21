@extends('layouts.app')

@section('title', $user ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('users.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke pengguna</a>
            <h1 class="page-title">{{ $user ? 'Ubah pengguna' : 'Tambah pengguna' }}</h1>
            <p class="page-sub">{{ $user ? 'Perbarui informasi akun dan hak akses pengguna.' : 'Buat akun baru untuk admin, koordinator kelas, atau orang tua.' }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}">
                @csrf
                @if($user)
                    @method('PUT')
                @endif

                <h3 class="form-section-title">Identitas akun</h3>

                <div class="form-grid">
                    <div class="field">
                        <label class="label" for="name">Nama lengkap <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $user?->name) }}" placeholder="Nama pengguna" required maxlength="120">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="phone">Nomor telepon</label>
                        <input type="text" name="phone" id="phone" class="input" value="{{ old('phone', $user?->phone) }}" placeholder="08xx xxxx xxxx" maxlength="30">
                        @error('phone') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="email">Alamat email <span class="req">*</span></label>
                        <input type="email" name="email" id="email" class="input" value="{{ old('email', $user?->email) }}" placeholder="nama@email.com" required maxlength="150">
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="password">Kata sandi {{ $user ? '' : '*' }}</label>
                        <input type="password" name="password" id="password" class="input" placeholder="{{ $user ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}" {{ $user ? '' : 'required' }} minlength="8" autocomplete="new-password">
                        <span class="hint">{{ $user ? 'Isi hanya jika ingin mengganti kata sandi.' : 'Minimal 8 karakter. Sampaikan kata sandi kepada pengguna.' }}</span>
                        @error('password') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="divider"></div>

                <h3 class="form-section-title">Peran & akses</h3>

                <div class="field">
                    <div class="radio-cards">
                        <label class="radio-card">
                            <input type="radio" name="role" value="orang_tua" @checked(old('role', $user?->role ?? 'orang_tua') === 'orang_tua')>
                            <span class="rc-title"><x-icon name="users" :size="15" /> Orang tua</span>
                            <span class="rc-desc">Melihat agenda dan kabar kelas anak. Kas dan iuran dikelola koordinator kelas.</span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="role" value="koordinator" @checked(old('role', $user?->role) === 'koordinator')>
                            <span class="rc-title"><x-icon name="school" :size="15" /> Koordinator</span>
                            <span class="rc-desc">Mengelola kas, iuran, agenda, dan pemberitahuan kelas yang ditugaskan.</span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="role" value="admin" @checked(old('role', $user?->role) === 'admin')>
                            <span class="rc-title"><x-icon name="shield" :size="15" /> Admin</span>
                            <span class="rc-desc">Akses penuh ke seluruh kelas, pengguna, dan verifikasi pembayaran.</span>
                        </label>
                    </div>
                    @error('role') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
                        <span>
                            <strong>Akun aktif</strong>
                            <small>Akun nonaktif tidak dapat masuk, tetapi datanya tetap tersimpan.</small>
                        </span>
                    </label>
                    @error('is_active') <span class="error">{{ $message }}</span> @enderror
                </div>

                @if($user?->isCoordinator())
                    <div class="notice mt-2">
                        <x-icon name="info" :size="15" />
                        <span>Penugasan koordinator ke kelas dilakukan melalui menu <strong>Kelas & siswa</strong>. Jika peran diubah dari koordinator, penugasan kelas akan dilepas.</span>
                    </div>
                @endif

                <div class="form-actions">
                    <a href="{{ route('users.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $user ? 'Simpan perubahan' : 'Buat akun' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
