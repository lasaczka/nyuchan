<?php

use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Command\Command;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nyuchan:version', function () {
    $this->line((string) config('app.version', '0.0.0'));

    return Command::SUCCESS;
})->purpose('Display current Nyuchan version');

Artisan::command('nyuchan:bootstrap-admin', function () {
    $username = trim((string) $this->ask('Admin username'));
    if ($username === '') {
        $this->error('Username cannot be empty.');

        return Command::FAILURE;
    }

    $password = (string) $this->secret('Admin password (min 6 chars)');
    if (mb_strlen($password) < 6) {
        $this->error('Password must be at least 6 characters.');

        return Command::FAILURE;
    }

    $passwordConfirmation = (string) $this->secret('Confirm password');
    if (! hash_equals($password, $passwordConfirmation)) {
        $this->error('Password confirmation mismatch.');

        return Command::FAILURE;
    }

    $user = User::query()->where('username', $username)->first();
    if ($user) {
        if (! $this->confirm("User '{$username}' exists. Promote to admin and reset password?", true)) {
            $this->warn('Aborted.');

            return Command::FAILURE;
        }

        $user->forceFill([
            'role' => Role::Admin,
            'password' => Hash::make($password),
        ])->save();

        $this->info("User '{$username}' updated as admin.");

        return Command::SUCCESS;
    }

    User::query()->create([
        'username' => $username,
        'role' => Role::Admin,
        'password' => Hash::make($password),
    ]);

    $this->info("Admin '{$username}' created.");

    return Command::SUCCESS;
})->purpose('Create or update the first admin user interactively');
