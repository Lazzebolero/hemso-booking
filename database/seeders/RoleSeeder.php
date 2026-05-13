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
        ];
    }
}