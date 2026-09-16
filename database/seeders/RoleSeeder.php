<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->roles() as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }

    protected function roles(): array
    {
        return [
            [
                'name' => 'Admin',
                'slug' => Roles::ADMIN,
                'description' => 'Administration och full kontroll',
            ],
            [
                'name' => 'Värd',
                'slug' => Roles::HOST,
                'description' => 'Bokningar och turplanering',
            ],
            [
                'name' => 'Guide',
                'slug' => Roles::GUIDE,
                'description' => 'Mobilanpassat guideläge',
            ],
            [
                'name' => 'Restaurang',
                'slug' => Roles::RESTAURANT,
                'description' => 'Statistik och meddelanden',
            ],
            [
                'name' => 'Trainee / elev',
                'slug' => Roles::ELEV,
                'description' => 'Schema och bemanning utan inloggning i appen',
            ],
            [
                'name' => 'Produktion admin',
                'slug' => Roles::PRODUKTION_ADMIN,
                'description' => 'TV-produktion: in/ut i berget och hantering av personer',
            ],
            [
                'name' => 'Produktion personal',
                'slug' => Roles::PRODUKTION_PERSONAL,
                'description' => 'TV-produktion: in/ut i berget och gruppstämpel för deltagare',
            ],
        ];
    }
}
