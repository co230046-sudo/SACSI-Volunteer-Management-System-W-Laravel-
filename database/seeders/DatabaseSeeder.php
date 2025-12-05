<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LocationsTableSeeder::class,     
            CoursesTableSeeder::class,    
            AdminAccountsSeeder::class,
            EventTypesTableSeeder::class,
             // OKVolunteerProfileSeeder::class,
        ]);
    }
}
