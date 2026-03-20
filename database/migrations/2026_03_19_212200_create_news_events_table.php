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
        Schema::create('news_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('airport_id')->nullable()->constrained()->nullOnDelete();
            $table->string('airline_code')->nullable();
            $table->timestamp('published_at');
            $table->string('title');
            $table->text('summary');
            $table->text('url');
            $table->string('category');
            $table->decimal('severity_score', 5, 2);
            $table->decimal('relevance_score', 5, 2);
            $table->foreignId('source_provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('raw_payload_id')->constrained('raw_provider_payloads')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['city_id', 'airport_id', 'published_at']);
            $table->index(['source_provider_id', 'published_at']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_events');
    }
};
