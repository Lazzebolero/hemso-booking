<?php

use App\Models\TourType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->foreignId('tour_type_id')->nullable()->after('title')->constrained('tour_types')->nullOnDelete();
        });

        // Migrera gamla värden från tours.tour_type till tours.tour_type_id
        if (Schema::hasColumn('tours', 'tour_type')) {
            $tours = DB::table('tours')->select('id', 'tour_type')->get();

            foreach ($tours as $tour) {
                if (blank($tour->tour_type)) {
                    continue;
                }

                $tourTypeId = null;

                // Om gamla värdet råkar vara ett ID
                if (is_numeric($tour->tour_type)) {
                    $existing = TourType::where('id', (int) $tour->tour_type)->first();
                    if ($existing) {
                        $tourTypeId = $existing->id;
                    }
                }

                // Annars försök matcha på namn
                if (!$tourTypeId) {
                    $existing = TourType::where('name', $tour->tour_type)->first();

                    if ($existing) {
                        $tourTypeId = $existing->id;
                    } else {
                        $created = TourType::create([
                            'name' => $tour->tour_type,
                            'sort_order' => 999,
                            'is_active' => true,
                            'is_default' => false,
                        ]);

                        $tourTypeId = $created->id;
                    }
                }

                DB::table('tours')
                    ->where('id', $tour->id)
                    ->update(['tour_type_id' => $tourTypeId]);
            }
        }

        // Ta bort gamla fältet tour_type
        if (Schema::hasColumn('tours', 'tour_type')) {
            Schema::table('tours', function (Blueprint $table) {
                $table->dropColumn('tour_type');
            });
        }
    }

    public function down(): void
    {
        // Återskapa gamla kolumnen om man skulle rollbacka
        Schema::table('tours', function (Blueprint $table) {
            $table->string('tour_type')->nullable()->after('title');
        });

        $tours = DB::table('tours')->select('id', 'tour_type_id')->get();

        foreach ($tours as $tour) {
            $name = null;

            if ($tour->tour_type_id) {
                $type = TourType::find($tour->tour_type_id);
                $name = $type?->name;
            }

            DB::table('tours')
                ->where('id', $tour->id)
                ->update(['tour_type' => $name]);
        }

        Schema::table('tours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tour_type_id');
        });
    }
};