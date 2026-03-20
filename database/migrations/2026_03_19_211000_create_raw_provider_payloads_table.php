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
        Schema::create('raw_provider_payloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->string('external_reference')->nullable();
            $table->jsonb('payload');
            $table->timestamp('fetched_at');
            $table->foreignId('ingestion_run_id')->constrained('ingestion_runs')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['provider_id', 'source_type', 'fetched_at']);
            $table->index('external_reference');
            $table->index('ingestion_run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_provider_payloads');
    }
};
