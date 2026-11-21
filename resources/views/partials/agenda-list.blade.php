<div class="agenda-list">
    @forelse($agendas as $agenda)
        <div class="agenda-row">
            <div class="date-block">
                <strong>{{ $agenda->starts_at->format('d') }}</strong>
                <span>{{ $agenda->starts_at->translatedFormat('M') }}</span>
            </div>
            <div class="agenda-body">
                <a href="{{ route('agenda.index', ['status' => 'all']) }}" class="agenda-title">{{ $agenda->title }}</a>
                <div class="agenda-meta">
                    <span><x-icon name="clock" :size="11" /> {{ $agenda->starts_at->format('H:i') }} WIB</span>
                    @if($agenda->location)
                        <span><x-icon name="pin" :size="11" /> {{ $agenda->location }}</span>
                    @endif
                    <span class="badge badge-neutral">{{ $agenda->classRoom?->name ?? 'Semua kelas' }}</span>
                </div>
            </div>
        </div>
    @empty
        <x-empty icon="calendar" title="Belum ada agenda" text="Agenda kelas akan tampil di sini." />
    @endforelse
</div>
