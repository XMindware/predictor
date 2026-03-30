<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RSS feed titles can far exceed 255 characters.
     * Change the column type from varchar(255) to text.
     */
    public function up(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            $table->text('title')->change();
        });
    }

    public function down(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            // Truncate back to 255 on rollback to avoid data errors
            $table->string('title', 255)->change();
        });
    }
};
