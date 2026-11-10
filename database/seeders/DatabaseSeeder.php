<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the demo data set.
     * Never run this against a production database.
     */
    public function run(): void
    {
        if (app()->isProduction() && ! $this->command->confirm('You are in PRODUCTION. Seed demo data anyway?', false)) {
            return;
        }
        $this->call(DemoSeeder::class);
    }
}
