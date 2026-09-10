<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's development/demo database.
     */
    public function run(): void
    {
        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
