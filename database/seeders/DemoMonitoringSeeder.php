<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CementMonitoringSeeder::class);
    }
}
