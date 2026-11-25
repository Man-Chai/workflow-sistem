@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="users" :size="13" /> Administrasi akses</span>
            <h1 class="page-title">Pengguna</h1>
            <p class="page-sub">Kelola akun admin, koordinator kelas, dan orang tua. Setiap peran memiliki akses yang berbeda.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah pengguna
            </a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="tabs">
            <a href="{{ route('users.index') }}" class="tab {{ ! $roleFilter ? 'is-active' : '' }}">
                Semua <span class="tab-count">{{ $counts['all'] }}</span>
            </a>
            <a href="{{ route('users.index', ['role' => 'admin']) }}" class="tab {{ $roleFilter === 'admin' ? 'is-active' : '' }}">
                Admin <span class="tab-count">{{ $counts['admin'] }}</span>
            </a>
            <a href="{{ route('users.index', ['role' => 'koordinator']) }}" class="tab {{ $roleFilter === 'koordinator' ? 'is-active' : '' }}">
                Koordinator <span class="tab-count">{{ $counts['koordinator'] }}</span>
            </a>
            <a href="{{ route('users.index', ['role' => 'orang_tua']) }}" class="tab {{ $roleFilter === 'orang_tua' ? 'is-active' : '' }}">
                Orang tua <span class="tab-count">{{ $counts['orang_tua'] }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('users.index') }}" class="filter-right">
            @if($roleFilter)
                <input type="hidden" name="role" value="{{ $roleFilter }}">
            @endif
            <div class="search-field">
                <x-icon name="search" :size="15" />
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Cari nama atau email...">
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Cari</button>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Peran</th>
                            <th>Keterhubungan</th>
                            <th>Status</th>
                            <th>Terakhir masuk</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <x-avatar :name="$user->name" />
                                        <span>
                                            <span class="cell-main">{{ $user->name }}</span>
                                            <span class="cell-sub">{{ $user->email }}</span>
                                            @if($user->phone || $user->lid)
                                                <span class="cell-sub tiny muted">
                                                    @if($user->phone) Telp: {{ $user->phone }} @endif
                                                    @if($user->phone && $user->lid) · @endif
                                                    @if($user->lid) LID: {{ $user->lid }} @endif
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $user->isAdmin() ? 'badge-violet' : ($user->isCoordinator() ? 'badge-brand' : 'badge-info') }}">
                                        {{ $user->roleLabel() }}
                                    </span>
                                </td>
                                <td class="small muted">
                                    @if($user->classesCoordinated->isNotEmpty())
                                        <span class="badge badge-brand">
                                            <x-icon name="school" :size="12" />
                                            {{ $user->classesCoordinated->pluck('name')->implode(', ') }}
                                        </span>
                                    @elseif($user->isParent())
                                        {{ $user->children_count }} anak terhubung
                                    @elseif($user->isCoordinator())
                                        Belum ada kelas
                                    @else
                                        Akses penuh
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-neutral' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="small muted nowrap">
                                    {{ $user->last_login_at?->diffForHumans() ?? 'Belum pernah' }}
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('users.edit', $user) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah {{ $user->name }}">
                                            <x-icon name="edit" :size="14" />
                                        </a>
                                        @unless($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('users.toggle', $user) }}" data-confirm="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?">
                                                @csrf
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px;" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun">
                                                    <x-icon :name="$user->is_active ? 'lock' : 'circle-check'" :size="14" />
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Hapus akun {{ $user->name }}? Data keterhubungan akan dilepas.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus akun">
                                                    <x-icon name="trash" :size="14" />
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-empty icon="users" title="Pengguna tidak ditemukan" text="Coba ubah kata kunci pencarian atau filter peran.">
                                        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">Tambah pengguna</a>
                                    </x-empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
@endsection
