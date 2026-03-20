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
        Schema::table('raw_provider_payloads', function (Blueprint $table) {
            $table->timestamp('normalized_at')->nullable()->after('fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_provider_payloads', function (Blueprint $table) {
            $table->dropColumn('normalized_at');
        });
    }
};
