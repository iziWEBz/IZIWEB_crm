<?php

namespace App\Modules\Management\Controllers;

use App\Modules\Management\Models\GlobalTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PMTaskController extends ManagementBaseController
{
    #[OA\Get(
        path: '/pm/tasks',
        summary: 'Список глобальних макро-завдань, призначених строго на поточного авторизованого PM (PM)',
        tags: ['Кабінет Менеджера / Завдання'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання списку особистих завдань',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function myTasks(Request $request): JsonResponse
    {
        // СУВОРЕ БІЗНЕС-ПРАВИЛО: Менеджер бачить тільки свої завдання
        $pmId = $request->user()->id;

        $tasks = GlobalTask::with(['project'])
            ->where('pm_id', $pmId)
            ->orderBy('deadline_at', 'asc')
            ->get()
            ->map(function ($task) {
                // Динамічно прораховуємо прапорець overdue для зручності фронтенду
                $task->is_overdue = $task->deadline_at->isPast() && $task->status !== 'completed';
                return $task;
            });

        return response()->json($tasks, 200);
    }

    #[OA\Patch(
        path: '/pm/tasks/{id}/status',
        summary: 'Зміна виробничого статусу завдання менеджером (PM)',
        tags: ['Кабінет Менеджера / Завдання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', description: 'Новий статус: in_progress або review', example: 'review'),
                    new OA\Property(property: 'result_url', type: 'string', description: 'Опціональне посилання на договір/Figma/акт', nullable: true, example: 'https://figma.com...')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Статус завдання успішно змінено'),
            new OA\Response(response: 403, description: 'Доступ заборонено (спроба змінити чуже завдання)'),
            new OA\Response(response: 422, description: 'Помилка валідації вхідних даних')
        ]
    )]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // Перевіряємо межі доступу PM-а (власність задачі через токен)
        $task = GlobalTask::where('id', $id)
            ->where('pm_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:in_progress,review'],
            'result_url' => ['nullable', 'string', 'max:1000']
        ]);

        $newStatus = $validated['status'];
        
        // Якщо менеджер передав нове посилання — записуємо його, якщо ні — залишаємо старе (або null)
        $resultUrl = array_key_exists('result_url', $validated) ? $validated['result_url'] : $task->result_url;

        $task->update([
            'status' => $newStatus,
            'result_url' => $resultUrl
        ]);

        return response()->json([
            'message' => 'Виробничий статус завдання успішно оновлено менеджером.',
            'task' => $task
        ], 200);
    }

}
