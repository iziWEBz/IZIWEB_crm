<?php

namespace App\Modules\Onboarding\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackendCourseSeeder extends Seeder
{
    /**
     * Наповнення курсами та тестами для Backend-напрямку.
     */
    public function run(): void
    {
        $trainId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Основи Backend-розробки: Вступ до архітектури студії',
            'description' => 'Навчальний контур для ознайомлення з регламентом написання чистих REST API фреймворком Laravel.',
            'specialization' => 'backend',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        $testId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Співбесіда: Backend Developer (Тестовий контур)',
            'description' => 'Перевірочний тест на знання Eloquent ORM, оптимізації PostgreSQL та безпеки REST-сервісів.',
            'specialization' => 'backend',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $trainId,
            'title' => 'Вступ до REST та Маршрутизація',
            'content' => '# Стандарти маршрутизації\nУ нашій компанії ми пишемо Pure Headless REST API. Передавайте дані виключно в JSON формат.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'b_q1', 'question_text' => 'Яка різниця між методами PUT та PATCH?',
                'options' => ['PUT повністю оновлює ресурс, PATCH — частково', 'PATCH оновлює повністю, PUT — частково', 'Різниці немає'],
                'correct_option_index' => 0, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $testId,
            'title' => 'Тест на знання Eloquent ORM & Баз Даних',
            'content' => '# Фінальна перевірка знань для джуніор розробників\nПройдіть тест нижче для допуску до практичного завдання.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'b_t1', 'question_text' => 'Як вирішується проблема N+1 запиту в Eloquent ORM Laravel?',
                'options' => ['Використанням жадібного завантаження (Eager Loading) через метод with()', 'Використанням кешування Redis', 'Проблема вирішується автоматично ядрами фреймворку'],
                'correct_option_index' => 0, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);
    }
}
