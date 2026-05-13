<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'phone' => '0700000000',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $host = User::updateOrCreate(
            ['email' => 'host@example.com'],
            [
                'name' => 'Entrévärd',
                'phone' => '0700000001',
                'password' => Hash::make('password'),
                'role' => 'host',
                'is_active' => true,
            ]
        );

        $guide = User::updateOrCreate(
            ['email' => 'guide@example.com'],
            [
                'name' => 'Guide',
                'phone' => '0700000002',
                'password' => Hash::make('password'),
                'role' => 'guide',
                'is_active' => true,
            ]
        );

        Tour::firstOrCreate(
            ['title' => 'Standardvisning 10:00', 'tour_date' => now()->addDay()->toDateString()],
            [
                'description' => 'Exempeltur',
                'start_time' => '10:00',
                'end_time' => '11:30',
                'max_participants' => 30,
                'guide_id' => $guide->id,
                'status' => 'planned',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );
    }
}
