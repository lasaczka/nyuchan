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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')
                ->references('id')
                ->on('threads')
                ->cascadeOnDelete();
            $table->integer('number_in_thread');
            $table->string('display_name')
                ->nullable();
            $table->text('body');
            $table->boolean('is_deleted')
                ->default(false);
            $table->timestamps();
            $table->unique(['thread_id', 'number_in_thread']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
