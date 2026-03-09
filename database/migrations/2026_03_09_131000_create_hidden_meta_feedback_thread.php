<?php

use App\Support\PostingGuard;
use App\ValueObjects\AbuseId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $metaBoardId = $this->ensureMetaBoard($now);
        $threadId = $this->ensureFeedbackThread($metaBoardId, $now);
        $postId = $this->ensureFeedbackPost($threadId, $now);
        $this->ensureFeedbackPostMeta($postId);
    }

    public function down(): void
    {
        // Intentionally no destructive rollback for production-safe content migration.
    }

    private function ensureMetaBoard($now): int
    {
        $board = DB::table('boards')->where('slug', 'meta')->first();
        if ($board) {
            return (int) $board->id;
        }

        $payload = [
            'slug' => 'meta',
            'title' => 'Meta',
            'is_hidden' => true,
            'description' => 'Nyuchan feedback and bug reports.',
            'default_anon_name' => 'Anonymous',
            'bump_limit' => 500,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('boards', 'thread_limit')) {
            $payload['thread_limit'] = 1000;
        }
        if (Schema::hasColumn('boards', 'post_rate_limit_count')) {
            $payload['post_rate_limit_count'] = 3;
        }
        if (Schema::hasColumn('boards', 'post_rate_limit_window_seconds')) {
            $payload['post_rate_limit_window_seconds'] = 60;
        }

        return (int) DB::table('boards')->insertGetId($payload);
    }

    private function ensureFeedbackThread(int $boardId, $now): int
    {
        $thread = DB::table('threads')
            ->where('board_id', $boardId)
            ->whereIn('title', ['Bugs and suggestions', 'meta-feedback-thread', 'fixing-nyuch-thread'])
            ->first();

        if ($thread) {
            return (int) $thread->id;
        }

        return (int) DB::table('threads')->insertGetId([
            'board_id' => $boardId,
            'title' => 'fixing-nyuch-thread',
            'bumped_at' => $now,
            'is_locked' => false,
            'owner_token_hash' => hash('sha256', 'meta-feedback-bootstrap-'.Str::uuid()->toString()),
            'owner_token_issued_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureFeedbackPost(int $threadId, $now): int
    {
        $post = DB::table('posts')
            ->where('thread_id', $threadId)
            ->orderBy('id')
            ->first();

        if ($post) {
            return (int) $post->id;
        }

        $payload = [
            'thread_id' => $threadId,
            'display_name' => null,
            'body' => "Bugs, features, wishes, weirdness.",
            'is_deleted' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('posts', 'display_color')) {
            $payload['display_color'] = null;
        }
        if (Schema::hasColumn('posts', 'is_sage')) {
            $payload['is_sage'] = false;
        }

        return (int) DB::table('posts')->insertGetId($payload);
    }

    private function ensureFeedbackPostMeta(int $postId): void
    {
        $exists = DB::table('post_metas')->where('post_id', $postId)->exists();
        if ($exists) {
            return;
        }

        DB::table('post_metas')->insert([
            'post_id' => $postId,
            'abuse_id' => AbuseId::fromUserId(0)->value(),
            'epoch' => PostingGuard::EPOCH,
        ]);
    }
};
