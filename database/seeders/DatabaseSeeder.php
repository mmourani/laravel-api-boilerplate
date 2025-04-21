<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Add all your seeder classes here
        $this->call([
            NovaAdminSeeder::class,
        ]);
    }
}