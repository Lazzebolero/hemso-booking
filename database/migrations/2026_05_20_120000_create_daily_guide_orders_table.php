<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_guide_orders', function (Blueprint $table) {
            $table->id();
            $table->date('guide_date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order');
            $table->string('source', 20)->default('schedule');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['guide_date', 'user_id']);
            $table->unique(['guide_date', 'sort_order']);
            $table->index('guide_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_guide_orders');
    }
};
