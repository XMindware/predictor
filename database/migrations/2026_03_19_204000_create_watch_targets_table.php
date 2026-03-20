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
        Schema::create('watch_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('origin_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('monitoring_priority')->default(1);
            $table->unsignedInteger('date_window_days')->default(7);
            $table->timestamps();

            $table->index(['enabled', 'monitoring_priority']);
            $table->index(['origin_city_id', 'destination_city_id']);
            $table->index(['origin_airport_id', 'destination_airport_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_targets');
    }
};
