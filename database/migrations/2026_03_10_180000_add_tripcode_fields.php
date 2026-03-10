<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tripcode_secret')) {
                $table->text('tripcode_secret')->nullable()->after('profile_color');
            }

            if (! Schema::hasColumn('users', 'use_tripcode')) {
                $table->boolean('use_tripcode')->default(false)->after('tripcode_secret');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'tripcode')) {
                $table->string('tripcode', 20)->nullable()->after('display_color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'tripcode')) {
                $table->dropColumn('tripcode');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'use_tripcode')) {
                $table->dropColumn('use_tripcode');
            }

            if (Schema::hasColumn('users', 'tripcode_secret')) {
                $table->dropColumn('tripcode_secret');
            }
        });
    }
};

