<?php

namespace App\Modules\ClientEnvironment\Controllers;

use App\Modules\ClientEnvironment\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminProjectController extends ClientEnvBaseController
{
    #[OA\Get(
        path: '/client-env/projects',
        summary: 'Отримання повного дерева глобальних контрактів та їх етапів (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання дерева проєктів',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function index(): JsonResponse
    {
        // Завантажуємо тільки батьківські проєкти (глобальні контракти)
        // та жадібно підтягуємо дочірні етапи (children), клієнта та бриф-шаблони
        $projectsTree = Project::with(['children.briefTemplate', 'client'])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projectsTree, 200);
    }

    #[OA\Post(
        path: '/client-env/projects/store',
        summary: 'Створення картки глобального контракту або дочірнього виробничого етапу (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'client_id', 'budget'],
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', description: 'ID батьківського проєкту (якщо це дочірній етап)', nullable: true, example: 1),
                    new OA\Property(property: 'client_id', type: 'integer', description: 'ID користувача-замовника', example: 1),
                    new OA\Property(property: 'brief_template_id', type: 'integer', description: 'ID брифу (обовʼязково для етапів)', nullable: true, example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Етап 1: Розробка UI/UX Дизайну магазину'),
                    new OA\Property(property: 'budget', type: 'integer', description: 'Бюджет у копійках', example: 15000000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Проєкт/Етап успішно створено'),
            new OA\Response(response: 422, description: 'Помилка валідації вхідних даних')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:projects,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'brief_template_id' => ['nullable', 'integer', 'exists:brief_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'integer', 'min:0'],
        ]);

        // Якщо передано parent_id — це дочірній етап, для нього валідуємо наявність брифу
        if (!empty($validated['parent_id']) && empty($validated['brief_template_id'])) {
            return response()->json(['message' => 'Для дочірнього виробничого етапу обовʼязково потрібно вказати ID шаблону брифу.'], 422);
        }

        // Автоматично виставляємо початковий виробничий статус
        $validated['status'] = 'briefing';

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Картку проєкту успішно створено',
            'project' => $project
        ], 201);
    }

    #[OA\Patch(
        path: '/client-env/projects/{id}/status',
        summary: 'Зміна виробничого статусу конкретного етапу або контракту (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', description: 'Новий статус', example: 'in_development')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Статус успішно оновлено'),
            new OA\Response(response: 404, description: 'Проєкт не знайдено')
        ]
    )]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:briefing,ready_for_sign,in_development,completed']
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Виробничий статус проєкту успішно змінено',
            'project' => $project
        ], 200);
    }

    #[OA\Put(
        path: '/client-env/projects/{id}/update',
        summary: 'Повне редагування всіх полів, звʼязків та JSONB-відповідей брифу проєкту (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'client_id', 'budget', 'status'],
                properties: [
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'client_id', type: 'integer', example: 1),
                    new OA\Property(property: 'brief_template_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Оновлена назва проєкту або етапу'),
                    new OA\Property(property: 'status', type: 'string', example: 'in_development'),
                    new OA\Property(property: 'budget', type: 'integer', example: 60000000),
                    new OA\Property(
                        property: 'brief_data', 
                        type: 'object', 
                        description: 'Оновлені чисті відповіді брифу клієнда у форматі JSON (пари ключ:значення)',
                        nullable: true
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Проєкт та його бриф-дані успішно оновлено'),
            new OA\Response(response: 404, description: 'Проєкт не знайдено'),
            new OA\Response(response: 422, description: 'Помилка валідації вхідних даних')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:projects,id'],
            'client_id' => ['required', 'integer', 'exists:users,id'],
            'brief_template_id' => ['nullable', 'integer', 'exists:brief_templates,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:briefing,ready_for_sign,in_development,completed'],
            'budget' => ['required', 'integer', 'min:0'],
            'brief_data' => ['nullable', 'array'], // Додано повну свободу редагування відповідей брифу менеджером!
        ]);

        // Валідація бізнес-логіки: якщо це дочірній етап (є parent_id), перевіряємо наявність брифу
        if (!empty($validated['parent_id']) && empty($validated['brief_template_id'])) {
            return response()->json(['message' => 'Для дочірнього виробничого етапу обовʼязково потрібно вказати ID шаблону брифу.'], 422);
        }

        // Захист від рекурсивної петлі: проєкт не може стати батьком самому собі
        if (!empty($validated['parent_id']) && (int)$validated['parent_id'] === $project->id) {
            return response()->json(['message' => 'Проєкт не може посилатися на самого себе як на батьківський контракт.'], 422);
        }

        $project->update($validated);

        return response()->json([
            'message' => 'Дані, звʼязки та відповіді брифу проєкту успішно оновлено',
            'project' => $project
        ], 200);
    }




    #[OA\Delete(
        path: '/client-env/projects/{id}',
        summary: 'Безповоротне видалення картки проєкту (Тільки для ролі Admin)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Проєкт успішно видалено з системи'),
            new OA\Response(response: 403, description: 'Доступ заборонено (PM не може видаляти проєкти)'),
            new OA\Response(response: 404, description: 'Проєкт не знайдено')
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        // Додатковий жорсткий замок на рівні методу: перевіряємо роль користувача через Spatie трейт
        if (!$request->user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Доступ заборонено. Тільки Керівництво (Admin) має право безповоротно видаляти проєкти з бази даних.'
            ], 403);
        }

        $project = Project::findOrFail($id);
        
        // Видаляємо проєкт (всі підписи, тікети та дочірні етапи видаляться каскадно завдяки СУБД)
        $project->delete();

        return response()->json(['message' => 'Проєкт та всі повʼязані з ним етапи успішно видалено з системи.'], 200);
    }
}
