<?php

namespace App\Modules\Onboarding\Controllers;

use App\Modules\Onboarding\Models\CandidateProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminReviewController extends OnboardingBaseController
{
    #[OA\Get(
        path: '/onboarding/reviews',
        summary: 'Список надісланих практичних завдань, що очікують оцінки PM-а (Admin / PM)',
        tags: ['Панель Ментора / Перевірка Робіт'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання списку робіт на перевірку',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function indexReviews(): JsonResponse
    {
        // Отримуємо всі записи прогресу зі статусом 'review'
        // За допомогою eager loading ('with') підтягуємо дані користувача та самого кроку (мінімізація SQL-запитів)
        $reviews = CandidateProgress::with(['user', 'step'])
            ->where('status', 'review')
            ->orderBy('updated_at', 'asc') // Спочатку старіші запити, які довше чекають
            ->get();

        return response()->json($reviews, 200);
    }

    #[OA\Patch(
        path: '/onboarding/reviews/{id}/verify',
        summary: 'Вердикт PM-а щодо практичної роботи стажера (Admin / PM)',
        tags: ['Панель Ментора / Перевірка Робіт'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID запису прогресу (candidate_progress.id)',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', description: 'Вердикт: passed або in_progress', example: 'passed'),
                    new OA\Property(property: 'comment', type: 'string', description: 'Текстове зауваження або фідбек при відхиленні', example: 'Перероби сітку у Figma, попливли відступи.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Вердикт успішно зафіксовано'),
            new OA\Response(response: 404, description: 'Запис перевірки не знайдено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function verifyReview(Request $request, int $id): JsonResponse
    {
        // Перевіряємо, що запис існує і дійсно очікує на рев'ю
        $progress = CandidateProgress::where('id', $id)
            ->where('status', 'review')
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:passed,in_progress'],
            'comment' => ['nullable', 'string', 'max:1000']
        ]);

        // Оновлюємо статус роботи стажера
        $updateData = [
            'status' => $validated['status']
        ];

        // Якщо PM передав коментар/зауваження, ми можемо залогіувати його в JSONB або окремо.
        // Згідно з ТЗ, якщо роботу відхилено, вона повертається в статус 'in_progress', а посилання на таску зануляється
        if ($validated['status'] === 'in_progress') {
            $updateData['task_url'] = null; // Стажер має надіслати нове виправлене посилання
        }

        $progress->update($updateData);

        return response()->json([
            'message' => $validated['status'] === 'passed' 
                ? 'Практичну роботу успішно затверджено. Наступний крок відкрито стажеру.' 
                : 'Роботу відхилено та повернуто стажеру на доопрацювання.',
            'progress' => $progress
        ], 200);
    }
}
