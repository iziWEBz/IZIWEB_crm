<?php

namespace App\Modules\ClientEnvironment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientEnvDatabaseSeeder extends Seeder
{
    /**
     * Наповнення бази даних еталонними шаблонами брифів, проєктами та етапами.
     */
    public function run(): void
    {
        // 1. Отримуємо ID першого клієнта з таблиці users (створеного раніше в IAM сідері)
        // Якщо клієнтів немає, беремо ID = 1 або будь-якого першого юзера для тестів
        $clientId = DB::table('users')->first()?->id ?? 1;

        // 2. СТВОРЮЄМО ЕТАЛОННИЙ ШАБЛОН БРИФУ (JSONB схема конструктора полів)
        $briefSchema = [
            'step_1_company' => [
                'title' => 'Інформація про компанію та бізнес',
                'fields' => [
                    [
                        'field_id' => 'brand_name',
                        'type' => 'string',
                        'label' => 'Назва вашої компанії або торгової марки',
                        'placeholder' => 'Введіть назву...',
                        'required' => true
                    ],
                    [
                        'field_id' => 'business_niche',
                        'type' => 'string',
                        'label' => 'Сфера діяльності та ніша бізнесу',
                        'placeholder' => 'Наприклад: E-commerce, логістика...',
                        'required' => true
                    ],
                    [
                        'field_id' => 'competitor_links',
                        'type' => 'text', // Довгий текст або масив посилань
                        'label' => 'Посилання на сайти ваших головних конкурентів',
                        'placeholder' => 'Кожне посилання з нового рядка...',
                        'required' => false
                    ]
                ]
            ],
            'step_2_technical' => [
                'title' => 'Технічні вимоги до платформи',
                'fields' => [
                    [
                        'field_id' => 'expected_products_count',
                        'type' => 'string',
                        'label' => 'Очікувана кількість товарів на сайті',
                        'placeholder' => 'Наприклад: до 5000, більше 50 000...',
                        'required' => true
                    ],
                    [
                        'field_id' => 'integrations_needed',
                        'type' => 'text',
                        'label' => 'Які зовнішні інтеграції необхідні? (CRM, 1C, Нова Пошта)',
                        'placeholder' => 'Перерахуйте необхідні сервіси...',
                        'required' => false
                    ]
                ]
            ]
        ];

        $templateId = DB::table('brief_templates')->insertGetId([
            'name' => 'Бриф: Розробка Інтернет-магазину',
            'service_type' => 'web_development',
            'schema_payload' => json_encode($briefSchema, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. СТВОРЮЄМО БАТЬКІВСЬКИЙ ПРОЄКТ (Глобальний контракт замовника)
        // Для нього brief_template_id та brief_data дорівнюють null
        $parentProjectId = DB::table('projects')->insertGetId([
            'parent_id' => null,
            'client_id' => $clientId,
            'brief_template_id' => null,
            'title' => 'Глобальний контракт: Створення E-commerce платформи для ТОВ Вектор',
            'status' => 'in_development', // Загальний статус проєкту в роботі
            'brief_data' => null,
            'budget' => 50000000, // Глобальний загальний бюджет (наприклад, 500 000 грн в копійках)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. СТВОРЮЄМО ДОЧІРНІЙ ЕТАП №1: UI/UX Дизайн
        // Клієнт уже заповнив бриф по цьому етапу (імітуємо готові відповіді у brief_data)
        $designAnswers = [
            'step_1_company' => [
                'brand_name' => 'Вектор Трейд',
                'business_niche' => 'Продаж будівельних матеріалів',
                'competitor_links' => "https://epicentrk.ua\nhttps://budmat.ua"
            ],
            'step_2_technical' => [
                'expected_products_count' => 'до 10 000 товарів',
                'integrations_needed' => 'Синхронізація з 1С та Новою Поштою'
            ]
        ];

        DB::table('projects')->insert([
            'parent_id' => $parentProjectId, // Прив'язка до батьківського глобального контракту
            'client_id' => $clientId,
            'brief_template_id' => $templateId, // Використовує створений вище шаблон брифу
            'title' => 'Етап 1: Проєктування та UI/UX Дизайн магазину',
            'status' => 'completed', // Дизайн вже завершено!
            'brief_data' => json_encode($designAnswers, JSON_UNESCAPED_UNICODE),
            'budget' => 15000000, // Бюджет суто на дизайн (150 000 грн)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. СТВОРЮЄМО ДОЧІРНІЙ ЕТАП №2: Бекенд та Фронтенд розробка
        // Цей етап тільки розпочався, бриф ще на стадії заповнення (answers = null або порожня чернетка)
        DB::table('projects')->insert([
            'parent_id' => $parentProjectId,
            'client_id' => $clientId,
            'brief_template_id' => $templateId,
            'title' => 'Етап 2: Програмування, інтеграції та посадка на CMS',
            'status' => 'briefing', // Статус: Клієнт зараз заповнює бриф
            'brief_data' => null, // Чернетка порожня
            'budget' => 35000000, // Бюджет на розробку (350 000 грн)
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
