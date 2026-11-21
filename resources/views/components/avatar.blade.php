@props(['name' => '', 'size' => 'md'])

@php
    $hue = crc32(mb_strtolower(trim($name))) % 360;
@endphp

<span class="avatar avatar-{{ $size }}" style="--avatar-hue: {{ $hue }}" title="{{ $name }}">
    {{ initials($name) }}
</span>
