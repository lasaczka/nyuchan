<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThreadSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        Post::query()->delete();
        Thread::query()->delete();

        Board::query()->each(function (Board $board) use ($faker) {
            $threadsToCreate = random_int(6, 10);

            for ($i = 0; $i < $threadsToCreate; $i++) {
                $thread = Thread::create([
                    'board_id' => $board->id,
                    'title' => Str::limit($faker->sentence(random_int(4, 9)), 120, ''),
                    'bumped_at' => now()->subMinutes(random_int(1, 5000)),
                    'is_locked' => false,
                    'owner_token_hash' => hash('sha256', Str::random(40)),
                    'owner_token_issued_at' => now()->subDays(random_int(1, 30)),
                ]);

                $postCount = random_int(3, 20);

                for ($n = 1; $n <= $postCount; $n++) {
                    Post::create([
                        'thread_id' => $thread->id,
                        'display_name' => random_int(0, 1) ? null : $faker->userName(),
                        'body' => $faker->realTextBetween(120, 900),
                        'is_deleted' => false,
                    ]);
                }

                $thread->update([
                    'bumped_at' => now()->subMinutes(random_int(0, 500)),
                ]);
            }
        });
    }
}
