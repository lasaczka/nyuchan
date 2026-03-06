<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            if (! Schema::hasColumn('boards', 'thread_limit')) {
                $table->unsignedInteger('thread_limit')->default(100)->after('default_anon_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            if (Schema::hasColumn('boards', 'thread_limit')) {
                $table->dropColumn('thread_limit');
            }
        });
    }
};
