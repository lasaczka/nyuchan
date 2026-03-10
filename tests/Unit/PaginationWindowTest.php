<?php

namespace Tests\Unit;

use App\ValueObjects\Pagination\PaginationWindow;
use Tests\TestCase;

class PaginationWindowTest extends TestCase
{
    public function test_builds_start_window_for_first_pages(): void
    {
        $window = PaginationWindow::fromInts(current: 1, total: 10, maxVisible: 7);

        $this->assertSame([1, 2, 3, 4, 5, '…', 10], $this->labels($window));
        $this->assertFalse($window->hasPrevious());
        $this->assertTrue($window->hasNext());
        $this->assertSame(1, $window->previousPage());
        $this->assertSame(2, $window->nextPage());
    }

    public function test_builds_middle_window_with_ellipses(): void
    {
        $window = PaginationWindow::fromInts(current: 5, total: 10, maxVisible: 7);

        $this->assertSame([1, '…', 4, 5, 6, '…', 10], $this->labels($window));
    }

    public function test_builds_end_window_for_last_pages(): void
    {
        $window = PaginationWindow::fromInts(current: 9, total: 10, maxVisible: 7);

        $this->assertSame([1, '…', 6, 7, 8, 9, 10], $this->labels($window));
        $this->assertTrue($window->hasPrevious());
        $this->assertTrue($window->hasNext());
        $this->assertSame(8, $window->previousPage());
        $this->assertSame(10, $window->nextPage());
    }

    /**
     * @return list<int|string>
     */
    private function labels(PaginationWindow $window): array
    {
        return array_map(static fn ($link) => $link->isEllipsis ? '…' : (int) $link->label, $window->links());
    }
}

