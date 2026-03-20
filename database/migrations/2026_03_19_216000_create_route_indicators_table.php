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
        Schema::create('route_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->timestamp('as_of');
            $table->date('travel_date')->nullable();
            $table->unsignedInteger('window_hours');
            $table->decimal('flight_score', 5, 2);
            $table->decimal('news_score', 5, 2);
            $table->decimal('combined_score', 5, 2);
            $table->json('supporting_factors')->nullable();
            $table->timestamps();

            $table->unique(['route_id', 'as_of', 'travel_date', 'window_hours'], 'route_indicators_unique_window');
            $table->index(['route_id', 'as_of']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_indicators');
    }
};
