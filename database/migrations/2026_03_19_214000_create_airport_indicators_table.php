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
        Schema::create('airport_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airport_id')->constrained()->cascadeOnDelete();
            $table->timestamp('as_of');
            $table->unsignedInteger('window_hours');
            $table->decimal('weather_score', 5, 2);
            $table->decimal('flight_score', 5, 2);
            $table->decimal('news_score', 5, 2);
            $table->decimal('combined_score', 5, 2);
            $table->json('supporting_factors')->nullable();
            $table->timestamps();

            $table->unique(['airport_id', 'as_of', 'window_hours']);
            $table->index(['airport_id', 'as_of']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airport_indicators');
    }
};
