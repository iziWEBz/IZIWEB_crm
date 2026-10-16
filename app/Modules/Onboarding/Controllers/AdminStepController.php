<?php

namespace App\Modules\Onboarding\Controllers;

use App\Modules\Onboarding\Models\OnboardingCourse;
use App\Modules\Onboarding\Models\KnowledgeBaseStep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminStepController extends OnboardingBaseController
{
    #[OA\Get(
        path: '/onboarding/courses/{course_id}/steps',
        summary: 'Список усіх завдань всередині конкретного курсу (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'course_id',
                in: 'path',
                description: 'ID курсу, кроки якого потрібно отримати',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання списку завдань',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
            new OA\Response(response: 404, description: 'Курс не знайдено')
        ]
    )]
    public function indexSteps(int $courseId): JsonResponse
    {
        // Перевіряємо існування курсу, якщо його немає — автоматично повернеться 404
        $course = OnboardingCourse::findOrFail($courseId);

        // Повертаємо кроки через зв'язок (вони вже відсортовані за sort_order ASC завдяки моделі)
        return response()->json($course->steps, 200);
    }

    #[OA\Post(
        path: '/onboarding/steps/store',
        summary: 'Додавання нового завдання в курс із авторозрахунком сортування (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['course_id', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'course_id', type: 'integer', example: 1),
                    new OA\Property(property: 'title', type: 'string', example: 'Основи роботи з Git та GitFlow'),
                    new OA\Property(property: 'content', type: 'string', example: 'Текст лекції про правила розгалуження в студії...'),
                    new OA\Property(
                        property: 'test_schema', 
                        type: 'array', 
                        items: new OA\Items(type: 'object'),
                        example: [
                            [
                                'question_id' => 1,
                                'question_text' => 'Яка гілка є основною для релізу?',
                                'options' => ['main', 'dev', 'feature'],
                                'correct_option_index' => 0,
                                'type' => 'single_choice'
                            ]
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Завдання успішно додано'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function storeStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:onboarding_courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required'],
            'test_schema' => ['nullable', 'array'] // Валідуємо структуру як масив
        ]);

        // Авторозрахунок порядкового номера строго всередині цього конкретного курсу
        $maxSortOrder = KnowledgeBaseStep::where('course_id', $validated['course_id'])
            ->max('sort_order') ?? 0;

        $validated['sort_order'] = $maxSortOrder + 1;
        $validated['is_active'] = true;

        $step = KnowledgeBaseStep::create($validated);

        return response()->json([
            'message' => 'Завдання успішно додано до курсу',
            'step' => $step
        ], 201);
    }
    #[OA\Put(
        path: '/onboarding/steps/{id}/update',
        summary: 'Повне редагування завдання (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID завдання (кроку)',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Основи роботи з Git (Оновлено)'),
                    new OA\Property(property: 'content', type: 'string', example: 'Оновлений текст лекції з детальними прикладами команд...'),
                    new OA\Property(
                        property: 'test_schema',
                        type: 'array',
                        items: new OA\Items(type: 'object'),
                        nullable: true
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Завдання успішно оновлено'),
            new OA\Response(response: 404, description: 'Завдання не знайдено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function updateStep(Request $request, int $id): JsonResponse
    {
        $step = KnowledgeBaseStep::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required'],
            'test_schema' => ['nullable', 'array']
        ]);

        $step->update($validated);

        return response()->json([
            'message' => 'Завдання успішно оновлено',
            'step' => $step
        ], 200);
    }

    #[OA\Patch(
        path: '/onboarding/steps/{id}/toggle-status',
        summary: 'Швидке перемикання активності конкретного завдання (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID завдання',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Статус завдання успішно змінено'),
            new OA\Response(response: 404, description: 'Завдання не знайдено')
        ]
    )]
    public function toggleStatusStep(int $id): JsonResponse
    {
        $step = KnowledgeBaseStep::findOrFail($id);
        
        $step->is_active = !$step->is_active;
        $step->save();

        return response()->json([
            'message' => 'Статус завдання успішно змінено',
            'is_active' => $step->is_active
        ], 200);
    }

    #[OA\Post(
        path: '/onboarding/steps/reorder',
        summary: 'Drag-and-Drop пересортування завдань (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],  
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['course_id', 'step_id', 'new_position'],
                properties: [
                    new OA\Property(property: 'course_id', type: 'integer', example: 1),
                    new OA\Property(property: 'step_id', type: 'integer', example: 4),
                    new OA\Property(property: 'new_position', type: 'integer', description: 'Нова позиція (починаючи з 1)', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Порядок завдань успішно змінено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function reorderStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:onboarding_courses,id'],
            'step_id' => ['required', 'integer', 'exists:knowledge_base_steps,id'],
            'new_position' => ['required', 'integer', 'min:1'],
        ]);

        $courseId = $validated['course_id'];
        $stepId = $validated['step_id'];
        $newPosition = $validated['new_position'];

        // Виконуємо зміщення всередині транзакції
        \Illuminate\Support\Facades\DB::transaction(function () use ($courseId, $stepId, $newPosition) {
            $step = KnowledgeBaseStep::where('id', $stepId)
                ->where('course_id', $courseId)
                ->firstOrFail();

            $oldPosition = $step->sort_order;

            if ($oldPosition === $newPosition) {
                return;
            }

            // Якщо посунули крок вперед (вниз по списку)
            if ($oldPosition < $newPosition) {
                KnowledgeBaseStep::where('course_id', $courseId)
                    ->whereBetween('sort_order', [$oldPosition + 1, $newPosition])
                    ->decrement('sort_order');
            } 
            // Якщо посунули крок назад (вгору по списку)
            else {
                KnowledgeBaseStep::where('course_id', $courseId)
                    ->whereBetween('sort_order', [$newPosition, $oldPosition - 1])
                    ->increment('sort_order');
            }

            $step->sort_order = $newPosition;
            $step->save();
        });

        return response()->json(['message' => 'Порядок завдань успішно змінено'], 200);
    }

    #[OA\Delete(
        path: '/onboarding/steps/{id}',
        summary: 'Видалення завдання з автоматичним перерахунком номерів сортування (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID завдання',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Завдання успішно видалено'),
            new OA\Response(response: 404, description: 'Завдання не знайдено')
        ]
    )]
    public function destroyStep(int $id): JsonResponse
    {
        $step = KnowledgeBaseStep::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($step) {
            $deletedOrder = $step->sort_order;
            $courseId = $step->course_id;

            // Видаляємо саме завдання (записи з candidate_progress видаляться каскадно завдяки СУБД)
            $step->delete();

            // Зсуваємо всі інші завдання цього курсу, які йшли слідом
            KnowledgeBaseStep::where('course_id', $courseId)
                ->where('sort_order', '>', $deletedOrder)
                ->decrement('sort_order');
        });

        return response()->json(['message' => 'Завдання успішно видалено'], 200);
    }


}
