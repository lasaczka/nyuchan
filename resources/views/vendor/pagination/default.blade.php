@if ($paginator->hasPages())
    @php
        $window = \App\ValueObjects\Pagination\PaginationWindow::fromInts(
            current: $paginator->currentPage(),
            total: $paginator->lastPage(),
            maxVisible: 7,
        );
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-nav">
        <div class="pagination-edge pagination-edge-left">
            @if (! $window->hasPrevious())
                <span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo;</span>
            @else
                <a class="pagination-link" href="{{ $paginator->url($window->previousPage()) }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
            @endif
        </div>

        <ul class="pagination-list">
            @foreach ($window->links() as $link)
                @if ($link->isEllipsis)
                    <li><a class="pagination-ellipsis" href="{{ $paginator->url((int) $link->page) }}">{{ $link->label }}</a></li>
                @else
                    @if ($link->isActive)
                        <li><span class="pagination-link is-active" aria-current="page">{{ $link->label }}</span></li>
                    @else
                        <li><a class="pagination-link" href="{{ $paginator->url((int) $link->page) }}">{{ $link->label }}</a></li>
                    @endif
                @endif
            @endforeach
        </ul>

        <div class="pagination-edge pagination-edge-right">
            @if ($window->hasNext())
                <a class="pagination-link" href="{{ $paginator->url($window->nextPage()) }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
            @else
                <span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
