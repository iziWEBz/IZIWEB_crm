<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\IAM\Database\Seeders\IAMDatabaseSeeder;
use App\Modules\Onboarding\Database\Seeders\OnboardingDatabaseSeeder;
use App\Modules\ClientEnvironment\Database\Seeders\ClientEnvDatabaseSeeder;
use App\Modules\Management\Database\Seeders\ManagementDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Запуск усіх головних сідерів CRM-системи.
     */
    public function run(): void
    {
        $this->call([
            IAMDatabaseSeeder::class,        // 1. Користувачі та ролі
            OnboardingDatabaseSeeder::class, // 2. Навчальні курси, лекції та тести
            ClientEnvDatabaseSeeder::class,  // 3. Клієнтські бріфи, батьківські контракти та етапи
            ManagementDatabaseSeeder::class, // 4. Глобальні завдання Керівництва
        ]);
    }
}
