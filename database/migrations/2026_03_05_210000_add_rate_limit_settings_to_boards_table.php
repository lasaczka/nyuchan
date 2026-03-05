<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            if (! Schema::hasColumn('boards', 'post_rate_limit_count')) {
                $table->unsignedSmallInteger('post_rate_limit_count')->default(3)->after('bump_limit');
            }

            if (! Schema::hasColumn('boards', 'post_rate_limit_window_seconds')) {
                $table->unsignedInteger('post_rate_limit_window_seconds')->default(60)->after('post_rate_limit_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            if (Schema::hasColumn('boards', 'post_rate_limit_window_seconds')) {
                $table->dropColumn('post_rate_limit_window_seconds');
            }

            if (Schema::hasColumn('boards', 'post_rate_limit_count')) {
                $table->dropColumn('post_rate_limit_count');
            }
        });
    }
};
