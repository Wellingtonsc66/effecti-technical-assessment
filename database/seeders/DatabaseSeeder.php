<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ]
        );

        Service::query()->firstOrCreate(
            ['name' => 'Serviço A'],
            ['monthly_base_value' => 100]
        );

        Service::query()->firstOrCreate(
            ['name' => 'Serviço B'],
            ['monthly_base_value' => 250]
        );
    }
}
