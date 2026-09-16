<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_country_logs')) {
            Schema::create('daily_country_logs', function (Blueprint $table) {
                $table->id();
                $table->date('log_date')->unique();
                $table->text('notes')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daily_country_log_country')) {
            Schema::create('daily_country_log_country', function (Blueprint $table) {
                $table->foreignId('daily_country_log_id')->constrained('daily_country_logs')->cascadeOnDelete();
                $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
                $table->primary(['daily_country_log_id', 'country_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_country_log_country');
        Schema::dropIfExists('daily_country_logs');
    }
};
