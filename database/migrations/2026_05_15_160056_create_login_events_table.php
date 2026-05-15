<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('event_type', 32);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index('ip_address');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_events');
    }
};
