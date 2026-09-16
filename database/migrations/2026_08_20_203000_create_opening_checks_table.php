<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('opening_checks')) {
            return;
        }

        Schema::create('opening_checks', function (Blueprint $table) {
            $table->id();
            $table->date('check_date')->unique();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->time('visitor_opens_at')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->string('status', 32)->default('in_progress');
            $table->json('items')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_checks');
    }
};
