<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_messages')) {
            Schema::create('system_messages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('message_type', 32)->default('message');
                $table->text('body')->nullable();
                $table->json('target_roles')->nullable();
                $table->boolean('is_important')->default(false);
                $table->unsignedTinyInteger('priority')->default(2);
                $table->boolean('popup_only')->default(false);
                $table->boolean('requires_ack')->default(false);
                $table->boolean('send_email')->default(false);
                $table->unsignedInteger('remind_every_minutes')->nullable();
                $table->timestamp('last_reminder_at')->nullable();
                $table->timestamp('next_reminder_at')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('system_message_user')) {
            Schema::create('system_message_user', function (Blueprint $table) {
                $table->foreignId('system_message_id')->constrained('system_messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamps();

                $table->primary(['system_message_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_message_user');
        Schema::dropIfExists('system_messages');
    }
};
