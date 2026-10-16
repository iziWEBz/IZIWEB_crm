<?php

namespace App\Modules\IAM\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class IAMDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Створюємо Головного Адміністратора (Керівництво)
        $admin = User::updateOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name' => 'Тест Адмін',
                'password' => Hash::make('admin123'),
                'profile_data' => [
                    'phone' => '+380671112233',
                    'position' => 'CEO / Founder'
                ]
            ]
        );
        $admin->assignRole('admin'); // Призначаємо роль через Spatie

        // 2. Створюємо Project Manager (PM)
        $pm = User::updateOrCreate(
            ['email' => 'pm@crm.local'],
            [
                'name' => 'Тест Менеджер',
                'password' => Hash::make('pm123'),
                'profile_data' => [
                    'phone' => '+380674445566',
                    'position' => 'Senior Project Manager'
                ]
            ]
        );
        $pm->assignRole('pm');

        // 3. Створюємо Лінійного спеціаліста (Розробник)
        $dev = User::updateOrCreate(
            ['email' => 'dev@crm.local'],
            [
                'name' => 'Тест Розробник',
                'password' => Hash::make('dev123'),
                'profile_data' => [
                    'phone' => '+380677778899',
                    'specialization' => 'backend'
                ]
            ]
        );
        $dev->assignRole('specialist');

        // 4. Створюємо Кандидата (Стажера)
        $candidate = User::updateOrCreate(
            ['email' => 'candidate@crm.local'],
            [
                'name' => 'Тест Стажер',
                'password' => Hash::make('candidate123'),
                'profile_data' => [
                    'phone' => '+380630001122',
                    'specialization' => 'frontend'
                ]
            ]
        );
        $candidate->assignRole('candidate');

        // 5. Створюємо Клієнта
        $client = User::updateOrCreate(
            ['email' => 'client@crm.local'],
            [
                'name' => 'Тест Замовник',
                'password' => Hash::make('client123'),
                'profile_data' => [
                    'phone' => '+380509998877',
                    'company_name' => 'IziWeb Studio Partner'
                ]
            ]
        );
        $client->assignRole('client');
    }
}
