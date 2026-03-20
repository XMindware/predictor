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
        Schema::create('weather_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('airport_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('event_time');
            $table->timestamp('forecast_for');
            $table->decimal('severity_score', 5, 2);
            $table->string('condition_code');
            $table->text('summary');
            $table->decimal('temperature', 8, 2)->nullable();
            $table->decimal('precipitation_mm', 8, 2)->nullable();
            $table->decimal('wind_speed', 8, 2)->nullable();
            $table->foreignId('source_provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('raw_payload_id')->constrained('raw_provider_payloads')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['city_id', 'airport_id', 'forecast_for']);
            $table->index(['source_provider_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_events');
    }
};
