@extends('layouts.app')

@section('title', $classRoom->name)

@section('content')
    @php
        $user = auth()->user();
        $target = $dues->sum('target');
        $verified = $dues->sum('verified');
        $rate = $target > 0 ? min(100, (int) round($verified / $target * 100)) : 0;
        $canManage = $user->isAdmin() || $user->canManageClass($classRoom->id);
    @endphp

    <div class="page-head">
        <div>
            <a href="{{ $user->isAdmin() ? route('classes.index') : route('dashboard') }}" class="card-link mb-2"><x-icon name="chevron-left" :size="12" /> Kembali</a>
            <span class="eyebrow"><x-icon name="school" :size="13" /> {{ $classRoom->academic_year }}</span>
            <h1 class="page-title">{{ $classRoom->name }}</h1>
            <p class="page-sub">{{ $classRoom->description ?: 'Kelola siswa, iuran, dan informasi kelas.' }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('cash.index', ['class' => $classRoom->id]) }}" class="btn btn-outline">
                <x-icon name="wallet" :size="16" />
                Buka kas
            </a>
            @if($user->isAdmin())
                <a href="{{ route('classes.edit', $classRoom) }}" class="btn btn-primary">
                    <x-icon name="edit" :size="15" />
                    Ubah kelas
                </a>
            @endif
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Siswa</span>
                <span class="stat-icon"><x-icon name="users" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $classRoom->students->count() }}</div>
            <div class="stat-meta">Terdaftar di kelas ini</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Saldo kas</span>
                <span class="stat-icon is-violet"><x-icon name="wallet" :size="17" /></span>
            </div>
            <div class="stat-value">{{ rupiah($balance) }}</div>
            <div class="stat-meta">Saldo berjalan kelas</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Iuran aktif</span>
                <span class="stat-icon is-amber"><x-icon name="receipt" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $classRoom->dues()->where('is_active', true)->count() }}</div>
            <div class="stat-meta">{{ $dues->count() }} total iuran</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Iuran terkumpul</span>
                <span class="stat-icon is-blue"><x-icon name="trending-up" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $rate }}%</div>
            <div class="stat-meta">{{ rupiah($verified) }} dari {{ rupiah($target) }}</div>
        </div>
    </div>

    <div class="grid grid-main">
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="card-title"><x-icon name="users" :size="16" /> Daftar siswa</h3>
                    <p class="card-sub">Data siswa dan orang tua yang terhubung.</p>
                </div>
            </div>
            <div class="card-body">
                <details class="inline-details mb-3">
                    <summary class="btn btn-sm btn-primary">
                        <x-icon name="plus" :size="13" /> Tambah siswa
                    </summary>
                    <div class="details-body">
                        <form method="POST" action="{{ route('students.store', $classRoom) }}">
                            @csrf
                            <div class="form-grid">
                                <div class="field">
                                    <label class="label" for="student-name">Nama siswa <span class="req">*</span></label>
                                    <input type="text" name="name" id="student-name" class="input" value="{{ old('name') }}" required maxlength="120">
                                    @error('name') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="field">
                                    <label class="label" for="student-nis">NIS <span class="req">*</span></label>
                                    <input type="text" name="nis" id="student-nis" class="input" value="{{ old('nis') }}" required maxlength="30">
                                    @error('nis') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="field">
                                    <label class="label" for="student-gender">Jenis kelamin</label>
                                    <select name="gender" id="student-gender" class="select">
                                        <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                                        <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label class="label" for="student-birth">Tanggal lahir</label>
                                    <input type="date" name="birth_date" id="student-birth" class="input" value="{{ old('birth_date') }}">
                                    @error('birth_date') <span class="error">{{ $message }}</span> @enderror
                                </div>
                                <div class="field field-full">
                                    <label class="label" for="student-parent">Akun orang tua</label>
                                    <select name="parent_id" id="student-parent" class="select">
                                        <option value="">Belum dihubungkan</option>
                                        @foreach($parents as $parent)
                                            <option value="{{ $parent->id }}" @selected((int) old('parent_id') === $parent->id)>{{ $parent->name }} · {{ $parent->email }}</option>
                                        @endforeach
                                    </select>
                                    <span class="hint">Hubungkan siswa ke akun orang tua agar orang tua dapat melihat agenda dan pemberitahuan kelas.</span>
                                </div>
                            </div>
                            <div class="form-actions" style="margin-top: 12px; padding-top: 12px;">
                                <button type="submit" class="btn btn-primary btn-sm">Simpan siswa</button>
                            </div>
                        </form>
                    </div>
                </details>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>NIS</th>
                                <th>Orang tua</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classRoom->students as $student)
                                <tr>
                                    <td>
                                        <div class="cell-user">
                                            <x-avatar :name="$student->name" size="sm" />
                                            <span>
                                                <span class="cell-main">{{ $student->name }}</span>
                                                <span class="cell-sub">{{ $student->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}{{ $student->birth_date ? ' · '.$student->birth_date->format('d M Y') : '' }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="mono">{{ $student->nis }}</td>
                                    <td>
                                        @if($student->parent)
                                            <span class="cell-main">{{ $student->parent->name }}</span>
                                            <span class="cell-sub">{{ $student->parent->email }}</span>
                                        @else
                                            <span class="badge badge-warning">Belum terhubung</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a href="{{ route('students.edit', $student) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah siswa">
                                                <x-icon name="edit" :size="14" />
                                            </a>
                                            <form method="POST" action="{{ route('students.destroy', $student) }}" data-confirm="Hapus {{ $student->name }} dari kelas? Riwayat pembayaran siswa juga akan terhapus.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus siswa">
                                                    <x-icon name="trash" :size="14" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty icon="users" title="Belum ada siswa" text="Tambahkan siswa ke kelas ini agar tagihan dan data orang tua dapat dikelola." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="info" :size="16" /> Informasi kelas</h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span>Tahun ajaran</span>
                            <strong>{{ $classRoom->academic_year }}</strong>
                        </div>
                        <div class="info-item">
                            <span>Jumlah siswa</span>
                            <strong>{{ $classRoom->students->count() }}</strong>
                        </div>
                        <div class="info-item is-full">
                            <span>Koordinator</span>
                            <strong>{{ $classRoom->coordinator?->name ?? 'Belum ditugaskan' }}</strong>
                        </div>
                        <div class="info-item is-full">
                            <span>Email koordinator</span>
                            <strong>{{ $classRoom->coordinator?->email ?? '—' }}</strong>
                        </div>
                    </div>

                    @if($classRoom->description)
                        <div class="notice mt-3">
                            <x-icon name="info" :size="15" />
                            <span>{{ $classRoom->description }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="card-title"><x-icon name="wallet" :size="16" /> Iuran kelas</h3>
                        <p class="card-sub">Target dan capaian tiap iuran.</p>
                    </div>
                    <a href="{{ route('cash.index', ['class' => $classRoom->id, 'tab' => 'iuran']) }}" class="card-link">Kelola</a>
                </div>
                <div class="card-body">
                    <div class="due-list">
                        @forelse($dues->take(4) as $row)
                            <div class="due-card">
                                <div class="due-card-head">
                                    <div>
                                        <h3>{{ $row['due']->title }}</h3>
                                        <div class="due-meta">
                                            <span>{{ $row['due']->period ?: 'Tanpa periode' }}</span>
                                            <span>·</span>
                                            <span>{{ $row['due']->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </div>
                                    </div>
                                    <span class="due-amount">{{ rupiah($row['due']->amount) }}</span>
                                </div>
                                <div class="due-progress">
                                    <div class="progress">
                                        <div class="progress-bar {{ $row['percent'] < 50 ? 'is-warn' : '' }}" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <div class="progress-meta">
                                        <span>{{ $row['percent'] }}% terkumpul</span>
                                        <span>Target {{ rupiah($row['target']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <x-empty icon="receipt" title="Belum ada iuran" text="Buat iuran untuk kelas ini melalui menu kas." />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
