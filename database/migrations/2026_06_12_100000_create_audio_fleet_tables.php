<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audio_devices')) {
            Schema::create('audio_devices', function (Blueprint $table) {
                $table->unsignedSmallInteger('id')->primary();
                $table->string('name');
                $table->string('location')->nullable();
                $table->string('hostname')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sounds')) {
            Schema::create('sounds', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('path', 500);
                $table->string('file_path', 500);
                $table->string('original_name');
                $table->string('mime_type', 127)->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('loudspeakers')) {
            Schema::create('loudspeakers', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('device_id');
                $table->string('side', 50);
                $table->foreignId('sound_id')->nullable()->constrained('sounds')->nullOnDelete();
                $table->boolean('status')->default(false);
                $table->timestamps();

                $table->foreign('device_id')->references('id')->on('audio_devices')->cascadeOnDelete();
                $table->unique(['device_id', 'side']);
                $table->index(['device_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loudspeakers');
        Schema::dropIfExists('sounds');
        Schema::dropIfExists('audio_devices');
    }
};
