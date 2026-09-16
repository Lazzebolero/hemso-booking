<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('opening_deviations')) {
            return;
        }

        Schema::create('opening_deviations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_check_id')->constrained('opening_checks')->cascadeOnDelete();
            $table->string('checkpoint_key', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->string('location')->nullable();
            $table->text('description');
            $table->text('immediate_action')->nullable();
            $table->string('informed_person')->nullable();
            $table->text('decision_before_opening')->nullable();
            $table->string('status', 32)->default('open');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['opening_check_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_deviations');
    }
};
