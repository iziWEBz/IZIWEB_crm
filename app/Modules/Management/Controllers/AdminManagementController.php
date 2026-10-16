<?php

namespace App\Modules\Management\Controllers;

use App\Modules\Management\Models\GlobalTask;
use App\Modules\ClientEnvironment\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminManagementController extends ManagementBaseController
{
    #[OA\Get(
        path: '/management/tasks/list',
        summary: 'Перегляд усіх глобальних завдань у системі з гнучкою фільтрацією (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'pm_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_overdue', in: 'query', description: 'Фільтр протермінованих (1 або 0)', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання списку завдань')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = GlobalTask::with(['project', 'pm']);

        if ($request->has('pm_id')) {
            $query->where('pm_id', $request->query('pm_id'));
        }
        if ($request->has('project_id')) {
            $query->where('project_id', $request->query('project_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        
        // Фільтр протермінованих завдань (дедлайн минув, але статус не completed)
        if ($request->query('is_overdue') == 1) {
            $query->where('deadline_at', '<', now())->where('status', '!=', 'completed');
        }

        $tasks = $query->orderBy('deadline_at', 'asc')->get();
        return response()->json($tasks, 200);
    }

    #[OA\Post(
        path: '/management/tasks',
        summary: 'Створення нового макро-завдання для PM з автооновленням статусу проєкту (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['project_id', 'pm_id', 'title', 'description', 'deadline_at'],
                properties: [
                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                    new OA\Property(property: 'pm_id', type: 'integer', example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Провести брифінг клієнта'),
                    new OA\Property(property: 'description', type: 'string', example: 'Узгодити бриф на розробку інтернет-магазину'),
                    new OA\Property(property: 'deadline_at', type: 'string', format: 'date-time', example: '2026-10-20 18:00:00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Завдання створено'),
            new OA\Response(response: 422, description: 'Помилка валідації')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'pm_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deadline_at' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        $validated['status'] = 'pending';
        $task = GlobalTask::create($validated);

        // БІЗНЕС-ТРИГЕР: Автоматично переводимо статус проєкту/етапу в briefing
        $project = Project::find($validated['project_id']);
        if ($project && $project->status !== 'briefing') {
            $project->update(['status' => 'briefing']);
        }

        return response()->json(['message' => 'Глобальне завдання успішно створено та призначено', 'task' => $task], 201);
    }

    #[OA\Put(
        path: '/management/tasks/{id}/update',
        summary: 'Повне редагування завдання або його перепризначення на іншого PM (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['project_id', 'pm_id', 'title', 'description', 'status', 'deadline_at'],
                properties: [
                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                    new OA\Property(property: 'pm_id', type: 'integer', example: 2),
                    new OA\Property(property: 'title', type: 'string', example: 'Оновлена назва завдання'),
                    new OA\Property(property: 'description', type: 'string', example: 'Оновлений опис кроку'),
                    new OA\Property(property: 'status', type: 'string', example: 'in_progress'),
                    new OA\Property(property: 'result_url', type: 'string', nullable: true, example: 'http://...'),
                    new OA\Property(property: 'deadline_at', type: 'string', format: 'date-time', example: '2026-10-25 12:00:00')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Завдання оновлено')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $task = GlobalTask::findOrFail($id);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'pm_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'string', 'in:pending,in_progress,review,completed'],
            'result_url' => ['nullable', 'string'],
            'deadline_at' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        $task->update($validated);
        return response()->json(['message' => 'Глобальне завдання успішно оновлено', 'task' => $task], 200);
    }
    #[OA\Patch(
        path: '/management/tasks/{id}/approve',
        summary: 'Підтвердження виконання макро-завдання Керівництвом (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Завдання офіційно закрите та виконане'),
            new OA\Response(response: 404, description: 'Завдання не знайдено')
        ]
    )]
    public function approve(int $id): JsonResponse
    {
        $task = GlobalTask::findOrFail($id);

        $task->update([
            'status' => 'completed'
        ]);

        return response()->json([
            'message' => 'Виконання завдання успішно підтверджено Керівництвом. Етап офіційно закрито.',
            'task' => $task
        ], 200);
    }

    #[OA\Delete(
        path: '/management/tasks/{id}',
        summary: 'Видалення завдання з перевіркою статусу виконання (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Завдання видалено'),
            new OA\Response(response: 422, description: 'Помилка бізнес-логіки: завдання вже на перевірці або виконано')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $task = GlobalTask::findOrFail($id);

        // Суворе бізнес-правило: забороняємо видаляти задачі на стадії перевірки або завершені
        if (in_array($task->status, ['review', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Заборонено видаляти завдання, які вже виконані або знаходяться на перевірці Керівництва.'
            ], 422);
        }

        $task->delete();

        return response()->json(['message' => 'Глобальне завдання успішно видалено з системи.'], 200);
    }

    #[OA\Get(
        path: '/management/dashboard/metrics',
        summary: 'Зведена бізнес-аналітика та метрики ефективності для Керівництва (Admin)',
        tags: ['Панель Керівництва / Контроль Виробництва'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання метрик ефективності студії')
        ]
    )]
    public function dashboardMetrics(): JsonResponse
    {
        // 1. Загальна кількість активних проєктів клієнтів у виробництві
        $activeProjectsCount = Project::where('status', '!=', 'completed')->count();

        // 2. Розрахунок KPI виконання завдань у встановлений строк
        $totalCompleted = GlobalTask::where('status', 'completed')->count();
        
        // Завдання, які були виконані в строк або тривають без протермінування
        $successfulTasks = GlobalTask::where('status', 'completed')->count(); 

        $totalTasks = GlobalTask::count();
        $kpiPercent = $totalTasks > 0 ? (int) round(($successfulTasks / $totalTasks) * 100) : 100;

        // 3. Список усіх критично протермінованих дедлайнів по всій студії
        $overdueTasks = GlobalTask::with(['project', 'pm'])
            ->where('deadline_at', '<', now())
            ->where('status', '!=', 'completed')
            ->orderBy('deadline_at', 'asc')
            ->get()
            ->map(function($task) {
                // Додаємо динамічний маркер overdue для фронтенду
                $task->is_overdue = true;
                return $task;
            });

        return response()->json([
            'metrics' => [
                'active_projects_production_count' => $activeProjectsCount,
                'tasks_efficiency_kpi_percent' => $kpiPercent,
                'total_tasks_count' => $totalTasks
            ],
            'critical_overdue_deadlines' => $overdueTasks
        ], 200);
    }
}