<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Reference data (roles/permissions) is always seeded. Demo content can be
     * skipped in production with: php artisan db:seed --class=RolePermissionSeeder
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
