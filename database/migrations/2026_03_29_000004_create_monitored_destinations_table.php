<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('iata', 3)->unique();
            $table->string('label')->nullable()->comment('Human-readable display name, e.g. "Cancún"');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(5)->comment('Higher = shown first; 1–10');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_destinations');
    }
};
