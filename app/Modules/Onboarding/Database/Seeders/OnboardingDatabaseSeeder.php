<?php

namespace App\Modules\Onboarding\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnboardingDatabaseSeeder extends Seeder
{
    /**
     * Запуск послідовного наповнення бази даних Onboarding через ізольовані сидери.
     */
    public function run(): void
    {
        // Вимикаємо перевірку зовнішніх ключів для коректного очищення повʼязаних таблиць
        Schema::disableForeignKeyConstraints();
        DB::table('knowledge_base_steps')->truncate();
        DB::table('onboarding_courses')->truncate();
        Schema::enableForeignKeyConstraints();

        // Викликаємо доменні сидери для кожної спеціалізації по черзі
        $this->call([
            BackendCourseSeeder::class,
            FrontendCourseSeeder::class,
            DesignCourseSeeder::class,
            SeoCourseSeeder::class,
        ]);
    }
}
