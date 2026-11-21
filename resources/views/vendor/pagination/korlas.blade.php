@php
    if (! isset($paginator)) {
        $paginator = null;
    }
@endphp

@if($paginator && $paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman">
        <span class="pagination-info">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </span>
        <div class="pagination-links">
            @if($paginator->onFirstPage())
                <span class="page-btn is-disabled" aria-disabled="true"><x-icon name="chevron-left" :size="14" /></span>
            @else
                <a class="page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><x-icon name="chevron-left" :size="14" /></a>
            @endif

            @foreach($elements as $element)
                @if(is_string($element))
                    <span class="page-ellipsis">{{ $element }}</span>
                @endif

                @if(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <span class="page-btn is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if($paginator->hasMorePages())
                <a class="page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><x-icon name="chevron-right" :size="14" /></a>
            @else
                <span class="page-btn is-disabled" aria-disabled="true"><x-icon name="chevron-right" :size="14" /></span>
            @endif
        </div>
    </nav>
@endif
