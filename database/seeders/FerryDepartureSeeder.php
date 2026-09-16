<?php

namespace Database\Seeders;

use App\Models\FerryDeparture;
use App\Support\FerryDayTypes;
use App\Support\FerryDirections;
use Illuminate\Database\Seeder;

class FerryDepartureSeeder extends Seeder
{
    public function run(): void
    {
        if (FerryDeparture::query()->exists()) {
            return;
        }

        $weekdayToIsland = [
            '06:00', '06:20', '06:40',
            '07:00', '07:20', '07:40',
            '08:00', '08:30',
            '09:00', '09:30',
            '10:00', '10:30',
            '11:00', '11:30',
            '12:00', '12:30',
            '13:00', '13:40',
            '14:00', '14:40',
            '15:00', '15:30',
            '16:00', '16:20', '16:40',
            '17:00', '17:30',
        ];

        $weekdayToMainland = [
            '06:30',
            '07:30',
            '08:30',
            '09:30',
            '10:30',
            '11:30',
            '12:30',
            '13:30',
            '14:30',
            '15:30',
            '16:30',
            '17:30',
        ];

        $weekendTimes = [
            '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '13:00', '13:30',
            '14:00', '14:30', '15:00', '15:30',
            '16:00', '16:30', '17:00',
        ];

        $this->seedDirection(FerryDirections::TO_ISLAND, FerryDayTypes::WEEKDAY, $weekdayToIsland);
        $this->seedDirection(FerryDirections::TO_MAINLAND, FerryDayTypes::WEEKDAY, $weekdayToMainland);
        $this->seedDirection(FerryDirections::TO_ISLAND, FerryDayTypes::WEEKEND, $weekendTimes);
        $this->seedDirection(FerryDirections::TO_MAINLAND, FerryDayTypes::WEEKEND, $weekendTimes);
    }

    /**
     * @param  list<string>  $times
     */
    private function seedDirection(string $direction, string $dayType, array $times): void
    {
        foreach ($times as $index => $time) {
            FerryDeparture::query()->create([
                'direction' => $direction,
                'day_type' => $dayType,
                'departure_time' => $time.':00',
                'requires_call' => false,
                'sort_order' => $index,
            ]);
        }
    }
}
