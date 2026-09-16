<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('postal_code_entries')) {
            return;
        }

        Schema::create('postal_code_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postal_code_collection_day_id')
                ->constrained('postal_code_collection_days')
                ->cascadeOnDelete();
            $table->string('postal_code', 5);
            $table->string('locality')->nullable();
            $table->string('municipality_name')->nullable();
            $table->string('county_code', 2)->nullable();
            $table->string('county_name')->nullable();
            $table->boolean('lookup_matched')->default(false);
            $table->timestamps();

            $table->index(['postal_code_collection_day_id', 'postal_code'], 'postal_entries_day_code_idx');
            $table->index('county_code', 'postal_entries_county_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_code_entries');
    }
};
