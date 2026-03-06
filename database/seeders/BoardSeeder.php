<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Board;

class BoardSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            [
                'slug' => 'a',
                'title' => 'Anime',
                'is_hidden' => false,
                'description' => 'Anime and manga',
                'default_anon_name' => 'Anon',
                'thread_limit' => 100,
                'bump_limit' => 250,
            ],
            [
                'slug' => 'b',
                'title' => 'Random',
                'is_hidden' => false,
                'description' => 'Random board',
                'default_anon_name' => 'Anonymous',
                'thread_limit' => 100,
                'bump_limit' => 300,
            ],
            [
                'slug' => 'bb',
                'title' => 'Random++',
                'is_hidden' => true,
                'description' => 'Hidden random board',
                'default_anon_name' => 'Invisible',
                'thread_limit' => 100,
                'bump_limit' => 300,
            ],
            [
                'slug' => 'nsfw',
                'title' => 'NSFW',
                'is_hidden' => false,
                'description' => 'Adult content',
                'default_anon_name' => 'Nameless',
                'thread_limit' => 100,
                'bump_limit' => 200,
            ],
            [
                'slug' => 'rf',
                'title' => 'Refuge',
                'is_hidden' => false,
                'description' => 'Refuge board',
                'default_anon_name' => 'Refugee',
                'thread_limit' => 100,
                'bump_limit' => 250,
            ],
        ];

        foreach ($boards as $board) {
            Board::updateOrCreate(
                ['slug' => $board['slug']],
                $board
            );
        }

        Board::query()
            ->whereNotIn('slug', collect($boards)->pluck('slug')->all())
            ->update(['is_hidden' => true]);
    }
}
