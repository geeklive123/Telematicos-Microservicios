@if ($products->hasPages())
    <nav class="pagination" aria-label="Paginación de productos">
        <span class="pagination-info">
            Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }} de {{ $products->total() }}
        </span>

        <div class="pagination-links">
            @if ($products->onFirstPage())
                <span class="page-link page-link-disabled">Anterior</span>
            @else
                <a class="page-link" href="{{ $products->previousPageUrl() }}" rel="prev">Anterior</a>
            @endif

            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                @if ($page === $products->currentPage())
                    <span class="page-link page-link-active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($products->hasMorePages())
                <a class="page-link" href="{{ $products->nextPageUrl() }}" rel="next">Siguiente</a>
            @else
                <span class="page-link page-link-disabled">Siguiente</span>
            @endif
        </div>
    </nav>
@endif
