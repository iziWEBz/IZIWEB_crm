<?php

namespace App\Modules\Onboarding\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrontendCourseSeeder extends Seeder
{
    /**
     * Наповнення курсами та тестами для Frontend-напрямку.
     */
    public function run(): void
    {
        $trainId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Компонентний підхід та стандарти адаптивного кодингу',
            'description' => 'Навчальний курс з методології побудови інтерфейсів, роботи з API та оптимізації рендерингу в браузері.',
            'specialization' => 'frontend',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        $testId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Співбесіда: Frontend Developer (Тестовий контур)',
            'description' => 'Фінальний зріз знань по асинхронному JS (Event Loop), життєвому циклу компонентів фреймворків та оптимізації завантаження UI.',
            'specialization' => 'frontend',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $trainId,
            'title' => 'Адаптивна верстка та побудова інтерфейсів',
            'content' => '# Flexbox vs Grid\nFlexbox використовується для одновимірного позиціонування (ряд або колонка). Grid призначений для складних двовимірних сіток.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'f_q1', 'question_text' => 'Яку розмірність контролює CSS Grid за замовчуванням?',
                'options' => ['Тільки горизонтальні ряди', 'Тільки вертикальні колонки', 'Двовимірну сітку (рядки та колонки одночасно)'],
                'correct_option_index' => 2, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $testId,
            'title' => 'Тест: Асинхронний JavaScript та Event Loop',
            'content' => '# Перевірочний контур знань рушія V8\nДайте відповідь на технічні питання щодо черги мікрозадач та макрозадач.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'f_t1', 'question_text' => 'Що виконається раніше всередині одного циклу Event Loop?',
                'options' => ['Макрозадача (setTimeout)', 'Мікрозадача (Promise.then)', 'Вони виконуються паралельно у фонових потоках'],
                'correct_option_index' => 1, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);
    }
}
