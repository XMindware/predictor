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
        Schema::create('city_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->dateTime('as_of');
            $table->unsignedInteger('window_hours');
            $table->decimal('weather_score', 5, 2);
            $table->decimal('news_score', 5, 2);
            $table->decimal('combined_score', 5, 2);
            $table->json('supporting_factors')->nullable();
            $table->timestamps();

            $table->unique(['city_id', 'as_of', 'window_hours'], 'city_indicators_city_asof_win_uq');
            $table->index(['city_id', 'as_of'], 'city_indicators_city_asof_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_indicators');
    }
};
