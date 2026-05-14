<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_dogs', function (Blueprint $table) {
            $table->id();
            $table->string('dog_name');
            $table->string('breed')->nullable();
            $table->string('owner_phone', 64)->nullable();
            $table->date('visit_date');
            $table->time('tour_start_time')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->string('registered_as_role', 32);
            $table->timestamps();

            $table->index(['visit_date', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_dogs');
    }
};
