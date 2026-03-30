<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the monolithic feed_map JSON blob in provider_configs with a
     * proper first-class table: one row per RSS/Atom feed URL.
     *
     * iata = NULL  → "default" / global feed (fetched for every watch target)
     * iata = 'CUN' → city-specific feed for that airport
     */
    public function up(): void
    {
        Schema::create('rss_news_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->string('iata', 3)->nullable()->comment('NULL = global default; IATA = city-specific');
            $table->string('name');
            $table->text('url');
            $table->string('language', 5)->default('en')->comment('BCP-47 tag, e.g. en, es, fr');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(5)->comment('Higher = fetched first within same IATA group');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['iata', 'is_active'], 'rss_sources_iata_active_idx');
            $table->unique(['provider_id', 'url'], 'rss_sources_provider_url_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_news_sources');
    }
};
