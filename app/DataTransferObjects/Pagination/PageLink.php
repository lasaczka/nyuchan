<?php

namespace App\DataTransferObjects\Pagination;

final readonly class PageLink
{
    private function __construct(
        public string $label,
        public ?int $page,
        public bool $isActive,
        public bool $isEllipsis,
    ) {
    }

    public static function page(int $page, bool $isActive = false): self
    {
        return new self((string) $page, $page, $isActive, false);
    }

    public static function ellipsis(int $jumpPage): self
    {
        return new self('…', $jumpPage, false, true);
    }
}

