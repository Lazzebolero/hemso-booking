<?php

namespace App\Console\Commands;

use App\Services\TourAutoCompleteService;
use Illuminate\Console\Command;

class AutoCompleteToursCommand extends Command
{
    protected $signature = 'tours:auto-complete';

    protected $description = 'Avsluta pågående turer automatiskt när planerad sluttid passerats utan förlängning';

    public function handle(TourAutoCompleteService $autoComplete): int
    {
        $count = $autoComplete->autoCompleteDueTours();

        if ($count > 0) {
            $this->info("Avslutade {$count} tur(er) automatiskt.");
        }

        return self::SUCCESS;
    }
}
