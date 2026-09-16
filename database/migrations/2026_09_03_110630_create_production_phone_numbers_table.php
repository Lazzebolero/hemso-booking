<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->string('label');
            $table->string('phone', 50);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['production_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_phone_numbers');
    }
};
