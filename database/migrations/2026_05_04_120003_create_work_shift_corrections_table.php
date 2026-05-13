<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkShiftCorrectionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('work_shift_corrections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('work_shift_id')
                ->constrained('work_shifts')
                ->cascadeOnDelete();

            $table->foreignId('changed_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('old_corrected_in_at')->nullable();
            $table->timestamp('old_corrected_out_at')->nullable();

            $table->timestamp('new_corrected_in_at')->nullable();
            $table->timestamp('new_corrected_out_at')->nullable();

            $table->text('reason');

            $table->timestamps();

            $table->index(['work_shift_id', 'changed_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shift_corrections');
    }
}