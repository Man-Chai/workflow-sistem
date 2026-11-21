@props(['payment'])

@if($payment->status === 'verified')
    <x-badge tone="success">Terverifikasi</x-badge>
@elseif($payment->status === 'rejected')
    <x-badge tone="danger">Ditolak</x-badge>
@else
    <x-badge tone="warning">Menunggu</x-badge>
@endif
