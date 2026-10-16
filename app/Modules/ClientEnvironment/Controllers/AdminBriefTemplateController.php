<?php

namespace App\Modules\ClientEnvironment\Controllers;

use App\Modules\ClientEnvironment\Models\BriefTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminBriefTemplateController extends ClientEnvBaseController
{
        #[OA\Get(
        path: '/client-env/brief-templates',
        summary: 'Отримання списку всіх доступних шаблонів брифів у системі (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання списку брифів',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function index(): JsonResponse
    {
        // Повертаємо всі шаблони брифів для вибору менеджером
        $templates = BriefTemplate::orderBy('name', 'asc')->get();
        return response()->json($templates, 200);
    }

        #[OA\Post(
        path: '/client-env/brief-templates/store', // <- Змінили шлях тут
        summary: 'Створення нового шаблону брифу з нуля (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'service_type', 'schema_payload'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Бриф: Новий кастомний шаблон'),
                    new OA\Property(property: 'service_type', type: 'string', example: 'web_development'),
                    new OA\Property(property: 'schema_payload', type: 'object', description: 'Початкове JSON-дерево кроків та полів конструктора')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Шаблон успішно створено з нуля'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'in:web_development,seo,design'],
            'schema_payload' => ['required', 'array'],
        ]);

        $template = BriefTemplate::create($validated);

        return response()->json([
            'message' => 'Шаблон брифу успішно створено',
            'template' => $template
        ], 201);
    }


    #[OA\Put(
        path: '/client-env/brief-templates/{id}/update',
        summary: 'Повне редагування всіх полів та всієї JSONB-структури брифу (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID шаблону брифу',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'service_type', 'schema_payload'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Бриф: Корпоративний сайт (Оновлений)'),
                    new OA\Property(property: 'service_type', type: 'string', example: 'web_development'),
                    new OA\Property(
                        property: 'schema_payload', 
                        type: 'object', 
                        description: 'Повне оновлене JSON-дерево кроків, полів та правил валідації конструктора'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Шаблон брифу та його схема успішно оновлені'),
            new OA\Response(response: 404, description: 'Шаблон не знайдено'),
            new OA\Response(response: 422, description: 'Помилка валідації')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $template = BriefTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'string', 'in:web_development,seo,design'],
            'schema_payload' => ['required', 'array'], // Дозволяємо приймати та перезаписувати будь-яку структуру схеми
        ]);

        // Оновлюємо модель (Laravel автоматично серіалізує масив назад у JSONB завдяки casts)
        $template->update($validated);

        return response()->json([
            'message' => 'Шаблон брифу та його внутрішня схема успішно оновлені',
            'template' => $template
        ], 200);
    }



    #[OA\Patch(
        path: '/client-env/brief-templates/{id}/fields',
        summary: 'Точкове додавання або видалення конкретного поля з існуючого шаблону (Admin / PM)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['step_key', 'action', 'field_data'],
                properties: [
                    new OA\Property(property: 'step_key', type: 'string', description: 'Ключ кроку, наприклад step_1_company', example: 'step_1_company'),
                    new OA\Property(property: 'action', type: 'string', description: 'Дія: add або remove', example: 'add'),
                    new OA\Property(property: 'field_data', type: 'object', description: 'Специфікація обʼєкта поля (для add) або обʼєкт із field_id (для remove)')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Шаблон брифу точково оновлено'),
            new OA\Response(response: 422, description: 'Помилка валідації')
        ]
    )]
    public function updateFields(Request $request, int $id): JsonResponse
    {
        $template = BriefTemplate::findOrFail($id);

        $validated = $request->validate([
            'step_key' => ['required', 'string'],
            'action' => ['required', 'string', 'in:add,remove'],
            'field_data' => ['required', 'array'],
        ]);

        $payload = $template->schema_payload;
        $stepKey = $validated['step_key'];
        $action = $validated['action'];
        $fieldData = $validated['field_data'];

        // Перевіряємо чи існує вказаний крок у схемі брифу
        if (!isset($payload[$stepKey])) {
            return response()->json(["message" => "Вказаний крок брифу [{$stepKey}] не знайдено в поточному шаблоні."], 422);
        }

        if ($action === 'add') {
            if (empty($fieldData['field_id'])) {
                return response()->json(["message" => "Для додавання поля параметр field_id є обовʼязковим."], 422);
            }
            
            // Захист від дублювання field_id всередині кроку
            foreach ($payload[$stepKey]['fields'] as $existingField) {
                if ($existingField['field_id'] === $fieldData['field_id']) {
                    return response()->json(["message" => "Поле з ідентифікатором [{$fieldData['field_id']}] вже існує в цьому кроці."], 422);
                }
            }

            $payload[$stepKey]['fields'][] = $fieldData;
        } else {
            // Логіка видалення поля за field_id
            if (empty($fieldData['field_id'])) {
                return response()->json(["message" => "Для видалення поля необхідно передати field_id."], 422);
            }

            $filteredFields = array_filter($payload[$stepKey]['fields'], function ($field) use ($fieldData) {
                return $field['field_id'] !== $fieldData['field_id'];
            });

            $payload[$stepKey]['fields'] = array_values($filteredFields);
        }

        // Записуємо оновлене JSON-дерево назад у PostgreSQL
        $template->schema_payload = $payload;
        $template->save();

        return response()->json([
            'message' => 'Структуру полів шаблону брифу успішно змінено',
            'template' => $template
        ], 200);
    }

    #[OA\Delete(
        path: '/client-env/brief-templates/{id}',
        summary: 'Видалення шаблону брифу з СУБД-захистом (Тільки для ролі Admin)',
        tags: ['Панель Менеджера / Клієнтський Контур'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Шаблон брифу успішно видалено з системи'),
            new OA\Response(response: 403, description: 'Доступ заборонено (Тільки для Admin)'),
            new OA\Response(response: 422, description: 'Помилка СУБД: до шаблону привʼязані активні проєкти')
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        if (!$request->user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Доступ заборонено. Тільки Керівництво (Admin) має право видаляти шаблони брифів.'
            ], 403);
        }

        $template = BriefTemplate::findOrFail($id);

        try {
            $template->delete();
            return response()->json(['message' => 'Шаблон брифу успішно видалено з системи.'], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            // Ловимо виключення нашої СУБД-директиви onRestrictDelete()
            return response()->json([
                'status' => 'error',
                'message' => 'Неможливо видалити шаблон брифу. До нього привʼязані діючі проєкти компанії. Спочатку видаліть або перепривʼяжіть проєкти.'
            ], 422);
        }
    }
}
