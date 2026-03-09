@if ($paginator->hasPages())
    @php
        $total = $paginator->lastPage();
        $current = $paginator->currentPage();
        $items = [];

        $makeEllipsis = static function (int $jumpPage): array {
            return ['type' => 'ellipsis', 'jump' => $jumpPage];
        };

        $clamp = static function (int $page, int $totalPages): int {
            return max(1, min($totalPages, $page));
        };

        if ($total <= 7) {
            for ($page = 1; $page <= $total; $page++) {
                $items[] = $page;
            }
        } elseif ($current <= 4) {
            $rightJump = $clamp((int) round((5 + $total) / 2), $total);
            $items = [1, 2, 3, 4, 5, $makeEllipsis($rightJump), $total];
        } elseif ($current >= ($total - 3)) {
            $leftJump = $clamp((int) round((1 + ($total - 4)) / 2), $total);
            $items = [1, $makeEllipsis($leftJump), $total - 4, $total - 3, $total - 2, $total - 1, $total];
        } else {
            $leftJump = $clamp((int) round((1 + ($current - 1)) / 2), $total);
            $rightJump = $clamp((int) round((($current + 1) + $total) / 2), $total);
            $items = [1, $makeEllipsis($leftJump), $current - 1, $current, $current + 1, $makeEllipsis($rightJump), $total];
        }
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-nav">
        <div class="pagination-edge pagination-edge-left">
            @if ($paginator->onFirstPage())
                <span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">&lsaquo;</span>
            @else
                <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
            @endif
        </div>

        <ul class="pagination-list">
            @foreach ($items as $item)
                @if (is_array($item) && ($item['type'] ?? null) === 'ellipsis')
                    <li><a class="pagination-ellipsis" href="{{ $paginator->url((int) $item['jump']) }}">…</a></li>
                @else
                    @php $page = (int) $item; @endphp
                    @if ($page === $current)
                        <li><span class="pagination-link is-active" aria-current="page">{{ $page }}</span></li>
                    @else
                        <li><a class="pagination-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                    @endif
                @endif
            @endforeach
        </ul>

        <div class="pagination-edge pagination-edge-right">
            @if ($paginator->hasMorePages())
                <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
            @else
                <span class="pagination-link is-disabled" aria-disabled="true" aria-label="@lang('pagination.next')">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
