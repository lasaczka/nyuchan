<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_attachments', function (Blueprint $table) {
            $table->string('thumb_path', 255)->nullable()->after('path');
            $table->unsignedInteger('thumb_width')->nullable()->after('height');
            $table->unsignedInteger('thumb_height')->nullable()->after('thumb_width');
        });
    }

    public function down(): void
    {
        Schema::table('post_attachments', function (Blueprint $table) {
            $table->dropColumn(['thumb_path', 'thumb_width', 'thumb_height']);
        });
    }
};
