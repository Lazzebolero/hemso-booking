<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('work_date')->index();

            // Original clock punches. These should never be overwritten by normal editing.
            $table->timestamp('clock_in_at_original')->nullable()->index();
            $table->timestamp('clock_out_at_original')->nullable();

            // Editable time used for reporting.
            $table->timestamp('start_at')->nullable()->index();
            $table->timestamp('end_at')->nullable();
            $table->unsignedSmallInteger('break_minutes')->default(0);

            // open = clocked in, draft = clocked out/editable, submitted = ready for admin review.
            $table->string('status', 20)->default('open')->index();

            $table->text('user_comment')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'work_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
