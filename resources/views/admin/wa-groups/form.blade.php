@extends('layouts.app')

@section('title', $group ? 'Ubah Grup WhatsApp' : 'Tambah Grup WhatsApp')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('wa-groups.index') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali ke grup WhatsApp</a>
            <h1 class="page-title">{{ $group ? 'Ubah grup WhatsApp' : 'Tambah grup WhatsApp' }}</h1>
            <p class="page-sub">{{ $group ? 'Perbarui informasi grup WhatsApp dan kelas yang terhubung.' : 'Daftarkan grup WhatsApp baru dan kaitkan dengan kelas (opsional).' }}</p>
        </div>
    </div>

    <div class="card form-card">
        <div class="card-body">
            <form method="POST" action="{{ $group ? route('wa-groups.update', $group) : route('wa-groups.store') }}">
                @csrf
                @if($group)
                    @method('PUT')
                @endif

                <div class="form-grid">
                    <div class="field field-full">
                        <label class="label" for="name">Nama grup WhatsApp <span class="req">*</span></label>
                        <input type="text" name="name" id="name" class="input" value="{{ old('name', $group?->name) }}" placeholder="Contoh: Paguyuban Orang Tua XII MIPA 3" required maxlength="100">
                        @error('name') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="group_id">ID Grup WhatsApp (JID) <span class="req">*</span></label>
                        <input type="text" name="group_id" id="group_id" class="input" value="{{ old('group_id', $group?->group_id) }}" placeholder="Contoh: 120363430567097312@g.us" required maxlength="50">
                        <span class="hint">ID unik grup WhatsApp dari OpenWA (biasanya diakhiri dengan <code>@g.us</code>).</span>
                        @error('group_id') <span class="error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field field-full">
                        <label class="label" for="class_id">Kelas yang terhubung</label>
                        <select name="class_id" id="class_id" class="select" data-searchable-select data-placeholder="Ketik nama kelas untuk mencari...">
                            <option value="">— Belum dihubungkan ke kelas —</option>
                            @foreach($classes as $classRoom)
                                <option value="{{ $classRoom->id_kelas }}" @selected((string) old('class_id', $group?->id_kelas) === (string) $classRoom->id_kelas)>
                                    {{ $classRoom->name }} ({{ $classRoom->academic_year }})
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">Kolom <code>id_kelas</code> bersifat opsional (nullable). Anda dapat mengosongkannya jika grup belum dikaitkan ke kelas tertentu.</span>
                        @error('class_id') <span class="error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('wa-groups.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" :size="15" />
                        {{ $group ? 'Simpan perubahan' : 'Simpan grup WA' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
