<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usersCount = max(1, (int) env('SEED_TEST_USERS_COUNT', 50));
        $modsCount = max(0, (int) env('SEED_TEST_MODS_COUNT', 3));
        $passwordHash = Hash::make((string) env('SEED_TEST_USERS_PASSWORD', '111111'));

        User::factory()
            ->count($usersCount)
            ->create([
                'password' => $passwordHash,
                'role' => Role::User,
            ]);

        if ($modsCount > 0) {
            User::factory()
                ->count($modsCount)
                ->create([
                    'password' => $passwordHash,
                    'role' => Role::Mod,
                ]);
        }
    }
}
