<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'show_name_with_tripcode')) {
                $table->boolean('show_name_with_tripcode')->default(false)->after('use_tripcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'show_name_with_tripcode')) {
                $table->dropColumn('show_name_with_tripcode');
            }
        });
    }
};

