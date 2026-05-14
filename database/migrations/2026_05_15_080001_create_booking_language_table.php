<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_language')) {
            return;
        }

        Schema::create('booking_language', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->primary(['booking_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_language');
    }
};
