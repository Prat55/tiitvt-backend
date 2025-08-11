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
        $this->call([
            UserWithRoleSeeder::class,
            CenterSeeder::class,
            CourseSeeder::class,
            StudentSeeder::class,
            StudentOnlySeeder::class,
            // SystemDataSeeder::class,
        ]);
    }
}
