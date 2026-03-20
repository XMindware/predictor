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
        Schema::create('flight_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_airport_id')->constrained('airports')->cascadeOnDelete();
            $table->foreignId('destination_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->string('airline_code')->nullable();
            $table->timestamp('event_time');
            $table->date('travel_date')->nullable();
            $table->decimal('cancellation_rate', 5, 2)->nullable();
            $table->decimal('delay_average_minutes', 8, 2)->nullable();
            $table->decimal('disruption_score', 5, 2);
            $table->text('summary');
            $table->foreignId('source_provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('raw_payload_id')->constrained('raw_provider_payloads')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['route_id', 'travel_date']);
            $table->index(['origin_airport_id', 'destination_airport_id', 'event_time']);
            $table->index(['source_provider_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_events');
    }
};
