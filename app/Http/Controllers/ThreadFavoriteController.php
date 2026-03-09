<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Thread;
use App\Services\ThreadFavoritesService;
use Illuminate\Http\Request;

class ThreadFavoriteController extends Controller
{
    public function __construct(private readonly ThreadFavoritesService $threadFavorites)
    {
    }

    public function toggle(Request $request, Board $board, Thread $thread)
    {
        abort_unless($thread->board_id === $board->id, 404);

        $user = $request->user();
        abort_unless($user, 403);
        abort_unless($this->threadFavorites->isAvailable(), 404);
        $this->threadFavorites->toggle($user, $thread);

        return back();
    }
}
