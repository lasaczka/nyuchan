<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModPanelController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ThreadController;
use App\Models\Board;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $boards = Board::query()
        ->where('is_hidden', false)
        ->with('latestBumpedThread')
        ->withCount(['threads', 'posts'])
        ->withCount([
            'posts as posts_last_24h' => function ($query) {
                $query->where('posts.created_at', '>=', now()->subDay());
            },
        ])
        ->withMax('posts as last_post_at', 'created_at')
        ->orderBy('slug')
        ->get();

    $boardStats = $boards->map(function (Board $board) {
        $postsLast24h = (int) ($board->posts_last_24h ?? 0);

        return [
            'slug' => $board->slug,
            'title' => $board->display_title,
            'threads_count' => (int) $board->threads_count,
            'posts_count' => (int) $board->posts_count,
            'last_post_at' => $board->last_post_at,
            'posts_last_24h' => $postsLast24h,
            'last_thread_id' => $board->latestBumpedThread?->id,
            'last_thread_title' => $board->latestBumpedThread?->title,
        ];
    });

    return view('welcome', [
        'boardStats' => $boardStats,
    ]);
})->name('dashboard');

Route::get('/theme/{theme}', [ThemeController::class, 'set'])->name('theme.set');
Route::get('/locale', [LocaleController::class, 'set'])->name('locale.set');
Route::view('/rules', 'rules')->name('rules');
if (app()->environment('local')) {
    Route::get('/_debug/500', fn () => abort(500))->name('debug.500');
}

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/mod', [ModPanelController::class, 'index'])->name('mod.index');
    Route::post('/mod/users/{targetUser}/role', [ModPanelController::class, 'updateUserRole'])
        ->whereNumber('targetUser')
        ->name('mod.users.role');
    Route::post('/mod/bans/{ban}/unban', [ModPanelController::class, 'unban'])
        ->whereNumber('ban')
        ->name('mod.bans.unban');
    Route::post('/mod/tools', [ModPanelController::class, 'toggleUi'])
        ->name('mod.tools.toggle');

    Route::get('/media/{attachment}', [MediaController::class, 'show'])
        ->whereNumber('attachment')
        ->name('media.show');
    Route::get('/media/{attachment}/{filename}', [MediaController::class, 'show'])
        ->whereNumber('attachment')
        ->name('media.file');

    Route::get('/{board:slug}', [BoardController::class, 'index'])->name('boards.show');
    Route::get('/{board:slug}/{thread}', [ThreadController::class, 'show'])
        ->whereNumber('thread')
        ->name('threads.show');
    Route::post('/{board:slug}/thread', [ThreadController::class, 'store'])->name('threads.store');
    Route::post('/{board:slug}/{thread}/post', [PostController::class, 'store'])
        ->whereNumber('thread')
        ->name('posts.store');

    Route::post('/{board:slug}/{thread}/delete', [ModerationController::class, 'deleteThread'])
        ->whereNumber('thread')
        ->name('mod.thread.delete');
    Route::post('/{board:slug}/{thread}/ban-author', [ModerationController::class, 'banThreadAuthor'])
        ->whereNumber('thread')
        ->name('mod.thread.ban_author');

    Route::post('/{board:slug}/settings', [ModerationController::class, 'updateBoardSettings'])
        ->name('mod.board.settings');
    Route::post('/{board:slug}/{thread}/post/{post}/delete', [ModerationController::class, 'deletePost'])
        ->whereNumber('thread')
        ->whereNumber('post')
        ->name('mod.post.delete');
    Route::post('/{board:slug}/{thread}/post/{post}/ban', [ModerationController::class, 'banPostAuthor'])
        ->whereNumber('thread')
        ->whereNumber('post')
        ->name('mod.post.ban');

    Route::post('/invites', [InviteController::class, 'create'])->name('invites.create');
});

Route::fallback(fn () => abort(404));
