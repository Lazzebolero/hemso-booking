<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tour_types')) {
            Schema::create('tour_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('default_duration_minutes')->default(90);
                $table->timestamps();
            });
        }

        $defaultTypeId = DB::table('tour_types')->where('is_default', true)->value('id');

        if ($defaultTypeId === null) {
            $defaultTypeId = DB::table('tour_types')->insertGetId([
                'name' => 'Standard turtyp',
                'sort_order' => 0,
                'is_active' => true,
                'is_default' => true,
                'default_duration_minutes' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('tours') && ! Schema::hasColumn('tours', 'tour_type_id')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->foreignId('tour_type_id')->nullable()->after('id')->constrained('tour_types')->nullOnDelete();
            });
        }

        if (Schema::hasTable('tours')) {
            DB::table('tours')->whereNull('tour_type_id')->update(['tour_type_id' => $defaultTypeId]);
        }

        if (! Schema::hasTable('tour_booking_pages')) {
            Schema::create('tour_booking_pages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
                $table->string('slug')->unique();
                $table->string('page_title');
                $table->text('page_text')->nullable();
                $table->text('thank_you_text')->nullable();
                $table->text('full_tour_text')->nullable();
                $table->text('booking_terms')->nullable();
                $table->decimal('adult_price', 10, 2)->default(0);
                $table->decimal('youth_price', 10, 2)->default(0);
                $table->decimal('child_price', 10, 2)->default(0);
                $table->string('confirmation_subject')->nullable();
                $table->text('confirmation_body')->nullable();
                $table->boolean('is_public')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_booking_pages');

        if (Schema::hasTable('tours') && Schema::hasColumn('tours', 'tour_type_id')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tour_type_id');
            });
        }

        Schema::dropIfExists('tour_types');
    }
};
