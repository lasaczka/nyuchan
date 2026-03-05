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
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')
                ->references('id')
                ->on('boards')
                ->cascadeOnDelete();
            $table->timestamp('bumped_at');
            $table->boolean('is_locked')
                ->default(false);
            $table->string('owner_token_hash');
            $table->timestamp('owner_token_issued_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
