<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facility_occupancy_settings')) {
            return;
        }

        Schema::create('facility_occupancy_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('extra_count')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_occupancy_settings');
    }
};
