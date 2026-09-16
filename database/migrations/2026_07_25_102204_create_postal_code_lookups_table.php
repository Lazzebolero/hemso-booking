<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('postal_code_lookups')) {
            return;
        }

        Schema::create('postal_code_lookups', function (Blueprint $table) {
            $table->string('postal_code', 5)->primary();
            $table->string('locality');
            $table->string('municipality_code', 4)->nullable();
            $table->string('municipality_name')->nullable();
            $table->string('county_code', 2)->nullable();
            $table->string('county_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_code_lookups');
    }
};
