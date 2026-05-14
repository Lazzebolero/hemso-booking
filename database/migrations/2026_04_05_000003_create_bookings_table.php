<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->boolean('is_waitlist')->default(false);
            $table->boolean('is_walk_in')->default(false);
            $table->string('arrival_status', 32)->default('booked');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->foreignId('moved_from_tour_id')->nullable()->constrained('tours')->nullOnDelete();
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
