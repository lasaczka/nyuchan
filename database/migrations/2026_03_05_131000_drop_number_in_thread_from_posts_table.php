<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL may use the composite unique index as support for FK on thread_id.
        // Ensure a standalone index exists before dropping the unique pair.
        try {
            DB::statement('ALTER TABLE posts ADD INDEX posts_thread_id_index (thread_id)');
        } catch (\Throwable $e) {
            // Ignore if index already exists or database does not require this step.
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique('posts_thread_id_number_in_thread_unique');
            $table->dropColumn('number_in_thread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->integer('number_in_thread')->default(0);
            $table->unique(['thread_id', 'number_in_thread']);
        });
    }
};
