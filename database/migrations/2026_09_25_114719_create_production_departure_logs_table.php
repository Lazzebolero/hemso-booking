<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_departure_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('production_person_id')->constrained('production_people')->cascadeOnDelete();
            $table->string('action', 16);
            $table->timestamp('occurred_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_id', 'occurred_at']);
        });

        $now = now();

        DB::table('production_people')
            ->whereNotNull('departed_at')
            ->orderBy('id')
            ->get(['id', 'production_id', 'departed_at'])
            ->each(function (object $person) use ($now): void {
                DB::table('production_departure_logs')->insert([
                    'production_id' => $person->production_id,
                    'production_person_id' => $person->id,
                    'action' => 'departed',
                    'occurred_at' => $person->departed_at,
                    'recorded_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_departure_logs');
    }
};
