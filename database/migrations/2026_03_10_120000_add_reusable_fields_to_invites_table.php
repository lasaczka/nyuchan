<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invites', function (Blueprint $table) {
            $table->unsignedInteger('max_uses')->nullable()->after('token');
            $table->unsignedInteger('uses_count')->default(0)->after('max_uses');
            $table->timestamp('expires_at')->nullable()->after('used_at');
            $table->boolean('is_active')->default(true)->after('expires_at');
        });

        DB::table('invites')
            ->whereNull('max_uses')
            ->update([
                'max_uses' => 1,
            ]);

        DB::table('invites')
            ->whereNotNull('used_at')
            ->update([
                'uses_count' => 1,
                'is_active' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('invites', function (Blueprint $table) {
            $table->dropColumn([
                'max_uses',
                'uses_count',
                'expires_at',
                'is_active',
            ]);
        });
    }
};

