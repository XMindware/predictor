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
        Schema::create('scoring_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->jsonb('weights');
            $table->jsonb('thresholds');
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->unique(['name', 'version'], 'scoring_profiles_name_version_uq');
            $table->index('active', 'scoring_profiles_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scoring_profiles');
    }
};
