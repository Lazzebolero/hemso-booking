<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_memories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->text('body')->nullable();
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('audio_duration_seconds')->nullable();
            $table->string('audio_mime_type')->nullable();
            $table->unsignedBigInteger('audio_size')->nullable();
            $table->text('context_note')->nullable();
            $table->string('location_text')->nullable();
            $table->string('era_text')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('consent_type', 20);
            $table->boolean('consent_given');
            $table->foreignId('tour_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collected_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('submitted');
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('collected_by');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_memories');
    }
};
