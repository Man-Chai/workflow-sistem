@extends('layouts.app')

@section('title', 'Grup WhatsApp')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow"><x-icon name="phone" :size="13" /> Integrasi bot & grup</span>
            <h1 class="page-title">Grup WhatsApp</h1>
            <p class="page-sub">Kelola grup WhatsApp yang terdaftar pada bot dan hubungkan dengan masing-masing kelas.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('wa-groups.create') }}" class="btn btn-primary">
                <x-icon name="plus" :size="16" />
                Tambah grup WA
            </a>
        </div>
    </div>

    <div class="stat-grid stat-grid-3">
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Total grup WA</span>
                <span class="stat-icon"><x-icon name="phone" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $totalGroups }}</div>
            <div class="stat-meta">Terdaftar di database bot</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Terhubung ke kelas</span>
                <span class="stat-icon is-blue"><x-icon name="school" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $linkedGroups }}</div>
            <div class="stat-meta">Grup yang sudah memiliki kelas</div>
        </div>
        <div class="stat">
            <div class="stat-top">
                <span class="stat-label">Belum terhubung</span>
                <span class="stat-icon is-amber"><x-icon name="alert" :size="17" /></span>
            </div>
            <div class="stat-value">{{ $unlinkedGroups }}</div>
            <div class="stat-meta">Grup tanpa tautan kelas (nullable)</div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="tabs">
            <a href="{{ route('wa-groups.index') }}" class="tab {{ ! $filter ? 'is-active' : '' }}">
                Semua <span class="tab-count">{{ $totalGroups }}</span>
            </a>
            <a href="{{ route('wa-groups.index', ['filter' => 'linked']) }}" class="tab {{ $filter === 'linked' ? 'is-active' : '' }}">
                Terhubung <span class="tab-count">{{ $linkedGroups }}</span>
            </a>
            <a href="{{ route('wa-groups.index', ['filter' => 'unlinked']) }}" class="tab {{ $filter === 'unlinked' ? 'is-active' : '' }}">
                Belum terhubung <span class="tab-count">{{ $unlinkedGroups }}</span>
            </a>
        </div>

        <form method="GET" action="{{ route('wa-groups.index') }}" class="filter-right">
            @if($filter)
                <input type="hidden" name="filter" value="{{ $filter }}">
            @endif
            <div class="search-field">
                <x-icon name="search" :size="15" />
                <input type="search" name="q" value="{{ $search }}" class="input" placeholder="Cari nama grup, ID grup, atau kelas...">
            </div>
            <button type="submit" class="btn btn-sm btn-outline">Cari</button>
            @if($search)
                <a href="{{ route('wa-groups.index', array_filter(['filter' => $filter])) }}" class="btn btn-sm btn-ghost">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Grup WA</th>
                            <th>ID Grup (JID)</th>
                            <th>Kelas Terhubung</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td>
                                    <div class="cell-user">
                                        <span class="stat-icon" style="width: 33px; height: 33px; border-radius: 10px;">
                                            <x-icon name="phone" :size="16" />
                                        </span>
                                        <span>
                                            <span class="cell-main">{{ $group->name }}</span>
                                            <span class="cell-sub tiny muted">UUID: {{ $group->id_grup_wa }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <code>{{ $group->group_id }}</code>
                                </td>
                                <td>
                                    @if($group->classRoom)
                                        <a href="{{ route('classes.show', $group->classRoom) }}" class="badge badge-brand" style="text-decoration: none;">
                                            <x-icon name="school" :size="13" />
                                            {{ $group->classRoom->name }}
                                        </a>
                                    @else
                                        <span class="badge badge-warning">Belum terhubung</span>
                                    @endif
                                </td>
                                <td class="small muted nowrap">
                                    {{ $group->updated_at?->translatedFormat('d M Y · H:i') ?? '—' }}
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('wa-groups.edit', $group) }}" class="icon-btn" style="width: 29px; height: 29px;" aria-label="Ubah grup {{ $group->name }}">
                                            <x-icon name="edit" :size="14" />
                                        </a>
                                        <form method="POST" action="{{ route('wa-groups.destroy', $group) }}" data-confirm="Hapus grup WA {{ $group->name }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn" style="width: 29px; height: 29px; color: var(--rose);" aria-label="Hapus grup">
                                                <x-icon name="trash" :size="14" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty icon="phone" title="Grup WhatsApp tidak ditemukan" text="Tambahkan grup WhatsApp baru atau ubah filter pencarian.">
                                        <a href="{{ route('wa-groups.create') }}" class="btn btn-sm btn-primary">Tambah grup WA</a>
                                    </x-empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $groups->links() }}
        </div>
    </div>
@endsection
