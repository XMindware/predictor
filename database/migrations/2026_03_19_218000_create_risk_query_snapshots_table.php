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
        Schema::create('risk_query_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('origin_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date');
            $table->decimal('score', 5, 2);
            $table->string('risk_level');
            $table->string('confidence_level');
            $table->jsonb('factors')->nullable();
            $table->timestamp('generated_at');

            $table->index(['route_id', 'travel_date', 'generated_at']);
            $table->index(['origin_airport_id', 'destination_airport_id', 'travel_date'], 'risk_snapshots_airport_query_idx');
            $table->index(['origin_city_id', 'destination_city_id', 'travel_date'], 'risk_snapshots_city_query_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_query_snapshots');
    }
};
