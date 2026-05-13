<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roller
        $this->call(RoleSeeder::class);

        // 2. Hämta roller
        $roles = Role::all()->keyBy('slug');

        // 3. Skapa users
        $admin = $this->createUser(
            'Admin',
            'admin@example.com',
            '0700000000'
        )->assignRoles([$roles[Roles::ADMIN]]);

        $host = $this->createUser(
            'Entrévärd',
            'host@example.com',
            '0700000001'
        )->assignRoles([$roles[Roles::HOST]]);

        $guide = $this->createUser(
            'Guide',
            'guide@example.com',
            '0700000002'
        )->assignRoles([$roles[Roles::GUIDE]]);

        $restaurant = $this->createUser(
            'Restaurang',
            'restaurant@example.com',
            '0700000003'
        )->assignRoles([$roles[Roles::RESTAURANT]]);

        // Multirole (viktig!)
        $multi = $this->createUser(
            'Anna Andersson',
            'multi@example.com',
            '0700000004'
        )->assignRoles([
            $roles[Roles::HOST],
            $roles[Roles::GUIDE],
        ]);

        // 4. Testtour
        Tour::firstOrCreate(
            [
                'title' => 'Standardvisning 10:00',
                'tour_date' => now()->addDay()->toDateString(),
            ],
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

    protected function createUser(string $name, string $email, string $phone): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
    }
}