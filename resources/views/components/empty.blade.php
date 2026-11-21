@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'text' => null])

<div class="empty">
    <span class="empty-icon"><x-icon :name="$icon" :size="22" /></span>
    <h3>{{ $title }}</h3>
    @if($text)
        <p>{{ $text }}</p>
    @endif
    @if(trim($slot) !== '')
        <div class="empty-action">{{ $slot }}</div>
    @endif
</div>
