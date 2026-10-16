<?php

namespace App\Modules\ClientEnvironment\Controllers;

use App\Modules\ClientEnvironment\Models\CommunicationTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminTicketController extends ClientEnvBaseController
{
    #[OA\Get(
        path: '/pm/tickets/moderation',
        summary: 'Список усіх тікетів від лінійних спеціалістів, що очікують модерації (Admin / PM)',
        tags: ['Панель Ментора / Модерація Тікетів'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'Фільтрація за категорією (tech_issue, documents, edits)',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання пулу тікетів на перевірку',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        // Отримуємо тікети зі статусом 'open' (очікують модерації)
        // Завдяки eager loading завантажуємо проєкт, автора та його батьківський контракт
        $query = CommunicationTicket::with(['project.parent', 'user'])
            ->where('status', 'open');

        // Опціональна фільтрація за категорією
        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        // Сортуємо: спочатку старіші за часом оновлення
        $tickets = $query->orderBy('updated_at', 'asc')->get();

        return response()->json($tickets, 200);
    }

    #[OA\Patch(
        path: '/pm/tickets/{id}/approve',
        summary: 'Схвалення тікета, заповнення тексту для клієнта та відправка в його кабінет (Admin / PM)',
        tags: ['Панель Ментора / Модерація Тікетів'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message_to_client'],
                properties: [
                    new OA\Property(property: 'message_to_client', type: 'string', example: 'Шановний клієнте, ми оновили адаптивну верстку головної сторінки. Будь ласка, перевірте.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Тікет успішно відмодеровано та відправлено клієнту'),
            new OA\Response(response: 404, description: 'Тікет не знайдено або вже оброблено'),
            new OA\Response(response: 422, description: 'Помилка валідації')
        ]
    )]
    public function approve(Request $request, int $id): JsonResponse
    {
        // Шукаємо тікет строго у статусі 'open'
        $ticket = CommunicationTicket::where('id', $id)
            ->where('status', 'open')
            ->firstOrFail();

        $validated = $request->validate([
            'message_to_client' => ['required', 'string', 'max:5000']
        ]);

        // Оновлюємо відмодерований текст та пускаємо тікет далі по воронці виробництва
        $ticket->update([
            'message_to_client' => $validated['message_to_client'],
            'status' => 'in_progress' // Змінюємо статус на виробничий
        ]);

        return response()->json([
            'message' => 'Тікет успішно відмодеровано та опубліковано для замовника.',
            'ticket' => $ticket
        ], 200);
    }

        #[OA\Post(
        path: '/pm/tickets/{id}/reply',
        summary: 'Відповідь менеджера (PM) всередину існуючого тікета клієнта (Admin / PM)',
        tags: ['Панель Ментора / Модерація Тікетів'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['message_to_client'],
                properties: [
                    new OA\Property(property: 'message_to_client', type: 'string', example: 'Ми прийняли ваші правки, розробники вже взяли задачу в роботу. Очікуйте апдейт.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Відповідь успішно додана в тікет'),
            new OA\Response(response: 404, description: 'Тікет не знайдено')
        ]
    )]
    public function replyToTicket(Request $request, int $id): JsonResponse
    {
        // Шукаємо активний тікет
        $ticket = CommunicationTicket::findOrFail($id);

        $validated = $request->validate([
            'message_to_client' => ['required', 'string', 'max:5000']
        ]);

        // Оновлюємо текст повідомлення для клієнта та переводим статус в очікування відповіді замовника
        $ticket->update([
            'message_to_client' => $validated['message_to_client'],
            'status' => 'waiting_client' // Статус: Очікує на реакцію клієнта
        ]);

        return response()->json([
            'message' => 'Відповідь клієнту успішно збережена та відправлена в його кабінет.',
            'ticket' => $ticket
        ], 200);
    }

    #[OA\Post(
        path: '/pm/tickets/store-for-client',
        summary: 'Створення нового ініціативного тікета менеджером (PM) для конкретного проєкту клієнта (Admin / PM)',
        tags: ['Панель Ментора / Модерація Тікетів'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['project_id', 'title', 'category', 'message_to_client'],
                properties: [
                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Запит доступу до платіжного шлюзу LiqPay'),
                    new OA\Property(property: 'category', type: 'string', description: 'tech_issue, documents, edits', example: 'documents'),
                    new OA\Property(property: 'message_to_client', type: 'string', example: 'Для налаштування онлайн-оплати надішліть, будь ласка, Public та Private ключі мерчанта.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Тікет для клієнта успішно створено'),
            new OA\Response(response: 422, description: 'Помилка валідації полів')
        ]
    )]
    public function storeTicketForClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:tech_issue,documents,edits'],
            'message_to_client' => ['required', 'string', 'max:5000']
        ]);

        // Створюємо новий тікет від імені PM-а, який одразу зʼявиться в кабінеті клієнта
        $ticket = CommunicationTicket::create([
            'project_id' => $validated['project_id'],
            'user_id' => $request->user()->id, // Автор — PM/Admin
            'title' => $validated['title'],
            'category' => $validated['category'],
            'status' => 'waiting_client', // Одразу виставляємо статус очікування відповіді замовника
            'message_to_client' => $validated['message_to_client'],
            'message_from_specialist' => null // Внутрішній сирий текст відсутній, бо ініціатор сам PM
        ]);

        return response()->json([
            'message' => 'Новий тікет для клієнта успішно створено та опубліковано.',
            'ticket' => $ticket
        ], 201);
    }

}
