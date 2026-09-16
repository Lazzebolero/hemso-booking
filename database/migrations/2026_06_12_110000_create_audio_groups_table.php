<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audio_groups')) {
            Schema::create('audio_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (Schema::hasTable('audio_devices') && ! Schema::hasColumn('audio_devices', 'audio_group_id')) {
            Schema::table('audio_devices', function (Blueprint $table) {
                $table->foreignId('audio_group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('audio_groups')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audio_devices') && Schema::hasColumn('audio_devices', 'audio_group_id')) {
            Schema::table('audio_devices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('audio_group_id');
            });
        }

        Schema::dropIfExists('audio_groups');
    }
};
