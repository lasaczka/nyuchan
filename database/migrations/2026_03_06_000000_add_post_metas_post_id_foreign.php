<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_metas') || ! Schema::hasTable('posts')) {
            return;
        }

        try {
            Schema::table('post_metas', function (Blueprint $table) {
                $table->foreign('post_id')
                    ->references('id')
                    ->on('posts')
                    ->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // Ignore if constraint already exists or DB does not support this operation in-place.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('post_metas')) {
            return;
        }

        try {
            Schema::table('post_metas', function (Blueprint $table) {
                $table->dropForeign(['post_id']);
            });
        } catch (\Throwable $e) {
            // Ignore if constraint does not exist.
        }
    }
};
