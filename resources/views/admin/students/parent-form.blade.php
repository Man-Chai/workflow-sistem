@extends('layouts.app')

@section('title', $parent ? 'Ubah Orang Tua' : 'Tambah Orang Tua')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('students.index', ['tab' => 'ortu']) }}" class="card-link mb-2">
                <x-icon name="chevron-left" :size="12" /> Kembali ke daftar orang tua
            </a>
            <h1 class="page-title">{{ $parent ? 'Ubah data orang tua' : 'Tambah orang tua' }}</h1>
            <p class="page-sub">{{ $parent ? 'Perbarui informasi kontak orang tua.' : 'Tambahkan data orang tua baru ke tabel ortu.' }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $parent ? route('parents.update', $parent) : route('parents.store') }}">
                @csrf
                @if($parent)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="name">Nama lengkap orang tua <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $parent?->name) }}" placeholder="Contoh: Ibu Siti Aminah" required maxlength="100">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="phone">Nomor telepon / WhatsApp</label>
                        <input type="text" name="phone" id="phone" class="input" value="{{ old('phone', $parent?->phone) }}" maxlength="30" placeholder="Contoh: 62813xxxx">
                        @error('phone') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="label" for="email">Alamat email</label>
                        <input type="email" name="email" id="email" class="input" value="{{ old('email', $parent?->email) }}" maxlength="100" placeholder="nama@email.com">
                        @error('email') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('students.index', ['tab' => 'ortu']) }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $parent ? 'Simpan perubahan' : 'Simpan orang tua' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
