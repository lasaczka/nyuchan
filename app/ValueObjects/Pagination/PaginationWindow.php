<?php

namespace App\ValueObjects\Pagination;

use App\DataTransferObjects\Pagination\PageLink;
use InvalidArgumentException;

final readonly class PaginationWindow
{
    /**
     * @param list<PageLink> $links
     */
    private function __construct(
        private PageNumber $current,
        private PageNumber $total,
        private int $maxVisible,
        private array $links,
    ) {
    }

    public static function fromInts(int $current, int $total, int $maxVisible = 7): self
    {
        if ($maxVisible < 5) {
            throw new InvalidArgumentException('maxVisible must be >= 5.');
        }

        $totalPage = PageNumber::fromInt(max(1, $total));
        $currentPage = PageNumber::fromInt(max(1, $current))->clamp(PageNumber::fromInt(1), $totalPage);

        $links = self::buildLinks($currentPage, $totalPage, $maxVisible);

        return new self($currentPage, $totalPage, $maxVisible, $links);
    }

    public function hasPrevious(): bool
    {
        return $this->current->value() > 1;
    }

    public function hasNext(): bool
    {
        return $this->current->value() < $this->total->value();
    }

    public function previousPage(): int
    {
        return max(1, $this->current->value() - 1);
    }

    public function nextPage(): int
    {
        return min($this->total->value(), $this->current->value() + 1);
    }

    /**
     * @return list<PageLink>
     */
    public function links(): array
    {
        return $this->links;
    }

    /**
     * @return list<PageLink>
     */
    private static function buildLinks(PageNumber $current, PageNumber $total, int $maxVisible): array
    {
        $totalValue = $total->value();
        $currentValue = $current->value();
        $links = [];

        if ($totalValue <= $maxVisible) {
            for ($page = 1; $page <= $totalValue; $page++) {
                $links[] = PageLink::page($page, $page === $currentValue);
            }

            return $links;
        }

        $middleCount = max(1, $maxVisible - 4); // first + last + 2 ellipsis placeholders
        $startBandEnd = $middleCount + 2;
        $endBandStart = $totalValue - ($middleCount + 1);

        if ($currentValue <= ($middleCount + 1)) {
            for ($page = 1; $page <= $startBandEnd; $page++) {
                $links[] = PageLink::page($page, $page === $currentValue);
            }
            $links[] = PageLink::ellipsis(self::middleJump($startBandEnd, $totalValue));
            $links[] = PageLink::page($totalValue, false);

            return $links;
        }

        if ($currentValue >= $endBandStart) {
            $links[] = PageLink::page(1, false);
            $links[] = PageLink::ellipsis(self::middleJump(1, $endBandStart));
            for ($page = $endBandStart; $page <= $totalValue; $page++) {
                $links[] = PageLink::page($page, $page === $currentValue);
            }

            return $links;
        }

        $sideCount = intdiv($middleCount, 2);
        $middleStart = $currentValue - $sideCount;
        $middleEnd = $middleStart + $middleCount - 1;

        if ($middleEnd >= $totalValue) {
            $shift = $middleEnd - $totalValue;
            $middleStart -= $shift;
            $middleEnd -= $shift;
        }

        $links[] = PageLink::page(1, false);
        $links[] = PageLink::ellipsis(self::middleJump(1, $middleStart));
        for ($page = $middleStart; $page <= $middleEnd; $page++) {
            $links[] = PageLink::page($page, $page === $currentValue);
        }
        $links[] = PageLink::ellipsis(self::middleJump($middleEnd, $totalValue));
        $links[] = PageLink::page($totalValue, false);

        return $links;
    }

    private static function middleJump(int $left, int $right): int
    {
        return max(1, (int) round(($left + $right) / 2));
    }
}

