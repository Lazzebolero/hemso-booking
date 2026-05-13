<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->string('booking_name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('men_count')->default(0);
            $table->unsignedInteger('women_count')->default(0);
            $table->unsignedInteger('youth_count')->default(0);
            $table->unsignedInteger('child_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['preliminary', 'confirmed', 'cancelled', 'completed'])->default('confirmed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
