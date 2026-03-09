@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-nav">
        <ul class="pagination-list">
            @if ($paginator->onFirstPage())
                <li><span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo;</span></li>
            @else
                <li><a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a></li>
            @endif

            @if ($paginator->hasMorePages())
                <li><a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a></li>
            @else
                <li><span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
