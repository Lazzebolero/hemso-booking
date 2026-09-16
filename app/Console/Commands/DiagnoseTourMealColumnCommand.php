<?php

namespace App\Console\Commands;

use App\Models\Tour;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiagnoseTourMealColumnCommand extends Command
{
    protected $signature = 'tours:diagnose-meal {tour? : Tour ID to inspect}';

    protected $description = 'Visa om default_includes_meal finns i databasen och på en tur';

    public function handle(): int
    {
        $hasColumn = Schema::hasColumn('tours', 'default_includes_meal');

        $this->info('Kolumn tours.default_includes_meal: '.($hasColumn ? 'JA' : 'NEJ'));

        if (! $hasColumn) {
            $this->error('Kör: php artisan migrate');

            return self::FAILURE;
        }

        $tourId = $this->argument('tour');

        if ($tourId === null) {
            $this->line('Ange tour-id för att se sparat värde, t.ex. php artisan tours:diagnose-meal 12');

            return self::SUCCESS;
        }

        $tour = Tour::query()->find($tourId);

        if ($tour === null) {
            $this->error("Tur {$tourId} hittades inte.");

            return self::FAILURE;
        }

        $raw = $tour->getAttributes()['default_includes_meal'] ?? 'NULL';

        $this->line("Tur #{$tour->id} {$tour->title}");
        $this->line("Rått DB-värde default_includes_meal: {$raw}");
        $this->line('Eloquent (bool): '.($tour->default_includes_meal ? 'true (Med mat)' : 'false (Ej mat)'));

        return self::SUCCESS;
    }
}
