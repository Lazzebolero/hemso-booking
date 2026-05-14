<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('tour_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->unsignedInteger('max_participants')->default(0);
            $table->foreignId('guide_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['planned', 'started', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tour_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
