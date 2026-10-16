<?php

namespace App\Modules\Management\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManagementDatabaseSeeder extends Seeder
{
    /**
     * Запуск наповнення бази даних еталонними глобальними завданнями для PM.
     */
    public function run(): void
    {
        DB::table('global_tasks')->truncate();

        $project = DB::table('projects')->whereNotNull('parent_id')->first();
        $pmUser = DB::table('users')->find(2);

        $pmId = $pmUser ? $pmUser->id : (DB::table('users')->first()?->id ?? 1);

        if (!$project) {
            return;
        }

        // Завдання 1: У роботі (В межах дедлайну)
        DB::table('global_tasks')->insert([
            'project_id' => $project->id,
            'pm_id' => $pmId,
            'title' => 'Провести первинний брифінг клієнта та сформувати ТЗ',
            'description' => 'Узгодити з замовником структуру брифу інтернет-магазину. Зафіксувати відповіді щодо інтеграцій з 1С.',
            'status' => 'in_progress',
            'result_url' => null,
            'deadline_at' => now()->addDays(5),
            'created_at' => now(), 'updated_at' => now()
        ]);

        // Завдання 2: На перевірці у Керівництва (З прикріпленою силкою на договір)
        DB::table('global_tasks')->insert([
            'project_id' => $project->id,
            'pm_id' => $pmId,
            'title' => 'Підписати юридичний договір та акт старту робіт',
            'description' => 'Контракт має бути підписаний клієнтом цифровим підписом у кабінеті. Перевірити валідність хешу.',
            'status' => 'review',
            'result_url' => 'http://localhost:8090/storage/contracts/contract_trade_vector.pdf',
            'deadline_at' => now()->addDays(2),
            'created_at' => now(), 'updated_at' => now()
        ]);

        // Завдання 3: ШТУЧНО ПРОТЕРМІНОВАНЕ ЗАВДАННЯ
        DB::table('global_tasks')->insert([
            'project_id' => $project->id,
            'pm_id' => $pmId,
            'title' => 'Передати дизайн-макет на етап фронтенд-верстки',
            'description' => 'Затвердити UI-кіт у клієнта, зробити експорт SVG-іконок та прокинути таску лінійним розробникам.',
            'status' => 'in_progress',
            'result_url' => null,
            'deadline_at' => now()->subDays(3), // Дедлайн минув 3 дні тому
            'created_at' => now()->subDays(10), 'updated_at' => now()
        ]);
    }
}
