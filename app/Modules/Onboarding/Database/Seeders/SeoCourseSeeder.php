<?php

namespace App\Modules\Onboarding\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeoCourseSeeder extends Seeder
{
    /**
     * Наповнення курсами та тестами для SEO-напрямку.
     */
    public function run(): void
    {
        $trainId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Технічний SEO-аудит та семантичне проектування сайту',
            'description' => 'Вступний курс про базові регламенти сканування сайтів пошуковими роботами та збір семантичного ядра.',
            'specialization' => 'seo',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        $testId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Співбесіда: SEO Specialist (Тестовий контур)',
            'description' => 'Професійний зріз знань алгоритмів Google, налаштування індексації, Core Web Vitals та роботи з Google Search Console.',
            'specialization' => 'seo',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $trainId,
            'title' => 'Основи індексації та керування роботами',
            'content' => '# Керування роботами Google\nФайл robots.txt вказує шлях для сканування, але не гарантує приховання від індексації. Для повної заборони індексу використовуйте тег noindex.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 's_q1', 'question_text' => 'Який інструмент дає 100% гарантію, що сторінка НЕ зʼявиться в пошуковій видачі Google?',
                'options' => ['Директива Disallow у файлі robots.txt', 'Мета-тег <meta name="robots" content="noindex">', 'Видалення сторінки з карти сайту sitemap.xml'],
                'correct_option_index' => 1, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $testId,
            'title' => 'Тест: Алгоритми Google та Core Web Vitals',
            'content' => '# Співбесіда SEO Specialist\nПеревірка знань сучасних метрик швидкості завантаження сайту, що впливають на ранжування у 2026 році.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 's_t1', 'question_text' => 'Яка метрика Core Web Vitals відповідає за візуальну стабільність сторінки під час завантаження?',
                'options' => ['LCP (Largest Contentful Paint)', 'INP (Interaction to Next Paint)', 'CLS (Cumulative Layout Shift)'],
                'correct_option_index' => 2, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);
    }
}
