<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bans')) {
            Schema::drop('bans');
        }

        Schema::create('bans', function (Blueprint $table): void {
            $table->id();
            $table->string('abuse_id');
            $table->string('epoch');
            $table->text('reason');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->index(['abuse_id', 'epoch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bans');
    }
};
