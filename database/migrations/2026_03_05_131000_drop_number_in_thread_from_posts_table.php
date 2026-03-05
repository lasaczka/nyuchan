<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
