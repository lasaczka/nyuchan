<?php

namespace App\Providers;

use App\Models\Board;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        PasswordRule::defaults(static fn () => PasswordRule::min(6));

        // Locale is applied via SetLocaleFromSession middleware.
        View::composer('layouts.navigation', function ($view): void {
            if (! Schema::hasTable('boards')) {
                $view->with('navBoards', collect());

                return;
            }

            $order = config('nyuchan.board_nav_order', []);
            $boards = Board::query()->where('is_hidden', false)->get();

            $sorted = $boards->sortBy(function (Board $board) use ($order): string {
                $index = array_search($board->slug, $order, true);
                $position = $index === false ? 9999 : $index;

                return str_pad((string) $position, 4, '0', STR_PAD_LEFT).'_'.$board->slug;
            })->sortBy('slug')->values();

            $view->with('navBoards', $sorted);
        });
    }
}
