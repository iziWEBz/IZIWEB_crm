<?php

namespace App\Modules\Onboarding\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DesignCourseSeeder extends Seeder
{
    /**
     * Наповнення курсами та тестами для UI/UX Design напрямку.
     */
    public function run(): void
    {
        $trainId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Проєктування інтерфейсів: Від регламенту до Figma',
            'description' => 'Навчальний гайд по внутрішній дизайн-системі студії, сіткам та правилам передачі макетів у розробку.',
            'specialization' => 'design',
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        $testId = DB::table('onboarding_courses')->insertGetId([
            'title' => 'Співбесіда: UI/UX Web Designer (Тестовий контур)',
            'description' => 'Перевірка розуміння UX-патернів, законів юзабіліті, типографіки та доступності інтерфейсів (WCAG).',
            'specialization' => 'design',
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $trainId,
            'title' => 'Регламент передачі дизайн-макетів розробникам',
            'content' => '# Експорт графіки\nУсі іконки та векторні елементи мають передаватися строго у форматі SVG. Растрові зображення — WebP або PNG.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'd_q1', 'question_text' => 'У якому форматі потрібно експортувати логотипи та іконки для розробників?',
                'options' => ['JPEG', 'PNG', 'SVG'],
                'correct_option_index' => 2, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);

        DB::table('knowledge_base_steps')->insert([
            'course_id' => $testId,
            'title' => 'Тест на розуміння UX-патернів та Доступності',
            'content' => '# Співбесіда UI/UX\nПитання базуються на стандартах WCAG та ергономіці мобільних інтерфейсів.',
            'sort_order' => 1, 'is_active' => true,
            'test_schema' => json_encode([[
                'question_id' => 'd_t1', 'question_text' => 'Що означає правило "безпечної зони для великого пальця" (Thumb Zone) в мобільному UX?',
                'options' => ['Критичні елементи навігації мають бути розташовані в нижній/середній частині екрана', 'Всі кнопки мають бути великого розміру', 'Інтерфейс повинен підтримувати розпізнавання відбитків пальців'],
                'correct_option_index' => 0, 'type' => 'single_choice'
            ]], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now()
        ]);
    }
}
