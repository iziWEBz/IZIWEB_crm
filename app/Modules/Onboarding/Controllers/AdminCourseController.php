<?php

namespace App\Modules\Onboarding\Controllers;

use App\Modules\Onboarding\Models\OnboardingCourse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdminCourseController extends OnboardingBaseController
{
    #[OA\Get(
        path: '/onboarding/courses',
        summary: 'Список усіх курсів у системі (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'specialization',
                in: 'query',
                description: 'Фільтрація за напрямком (backend, frontend, design, seo)',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання списку курсів',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OnboardingCourse::query();

        if ($request->has('specialization')) {
            $query->where('specialization', $request->query('specialization'));
        }

        $courses = $query->orderBy('sort_order', 'asc')->get();

        return response()->json($courses, 200);
    }

    #[OA\Post(
        path: '/onboarding/courses/store',
        summary: 'Створення нового курсу з авторозрахунком сортування (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'specialization'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Основи Frontend-розробки'),
                    new OA\Property(property: 'description', type: 'string', example: 'Курс по Vue.js та Vite'),
                    new OA\Property(property: 'specialization', type: 'string', example: 'frontend')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Курс успішно створено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'specialization' => ['required', 'string', 'in:backend,frontend,design,seo'],
        ]);

        $maxSortOrder = OnboardingCourse::where('specialization', $validated['specialization'])
            ->max('sort_order') ?? 0;

        $validated['sort_order'] = $maxSortOrder + 1;
        $validated['is_active'] = true;

        $course = OnboardingCourse::create($validated);

        return response()->json([
            'message' => 'Курс успішно створено',
            'course' => $course
        ], 201);
    }

    #[OA\Put(
        path: '/onboarding/courses/{id}/update',
        summary: 'Редагування текстових полів курсу (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', example: 'Основи Frontend-розробки (Оновлено)'),
                    new OA\Property(property: 'description', type: 'string', example: 'Нова програма навчання по Vue 3')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Курс успішно оновлено'),
            new OA\Response(response: 404, description: 'Курс не знайдено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $course = OnboardingCourse::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Курс успішно оновлено',
            'course' => $course
        ], 200);
    }

    #[OA\Patch(
        path: '/onboarding/courses/{id}/toggle-status',
        summary: 'Швидке перемикання активності курсу (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Статус курсу успішно змінено'),
            new OA\Response(response: 404, description: 'Курс не знайдено')
        ]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $course = OnboardingCourse::findOrFail($id);
        $course->is_active = !$course->is_active;
        $course->save();

        return response()->json([
            'message' => 'Статус курсу успішно змінено',
            'is_active' => $course->is_active
        ], 200);
    }

    #[OA\Post(
        path: '/onboarding/courses/reorder',
        summary: 'Drag-and-Drop пересортування курсів (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['specialization', 'course_id', 'new_position'],
                properties: [
                    new OA\Property(property: 'specialization', type: 'string', example: 'backend'),
                    new OA\Property(property: 'course_id', type: 'integer', example: 5),
                    new OA\Property(property: 'new_position', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Порядок курсів успішно змінено'),
            new OA\Response(response: 422, description: 'Помилка валідації даних')
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'specialization' => ['required', 'string', 'in:backend,frontend,design,seo'],
            'course_id' => ['required', 'integer', 'exists:onboarding_courses,id'],
            'new_position' => ['required', 'integer', 'min:1'],
        ]);

        $courseId = $validated['course_id'];
        $newPosition = $validated['new_position'];
        $spec = $validated['specialization'];

        DB::transaction(function () use ($courseId, $newPosition, $spec) {
            $course = OnboardingCourse::where('id', $courseId)->where('specialization', $spec)->firstOrFail();
            $oldPosition = $course->sort_order;

            if ($oldPosition === $newPosition) {
                return;
            }

            if ($oldPosition < $newPosition) {
                OnboardingCourse::where('specialization', $spec)
                    ->whereBetween('sort_order', [$oldPosition + 1, $newPosition])
                    ->decrement('sort_order');
            } else {
                OnboardingCourse::where('specialization', $spec)
                    ->whereBetween('sort_order', [$newPosition, $oldPosition - 1])
                    ->increment('sort_order');
            }

            $course->sort_order = $newPosition;
            $course->save();
        });

        return response()->json(['message' => 'Порядок курсів успішно змінено'], 200);
    }

    #[OA\Delete(
        path: '/onboarding/courses/{id}',
        summary: 'Видалення курсу з автоматичним каскадним зсувом номерів (Admin / PM)',
        tags: ['Модуль Onboarding / Навчання'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Курс успішно видалено'),
            new OA\Response(response: 404, description: 'Курс не знайдено')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $course = OnboardingCourse::findOrFail($id);

        DB::transaction(function () use ($course) {
            $deletedOrder = $course->sort_order;
            $spec = $course->specialization;

            $course->delete();

            OnboardingCourse::where('specialization', $spec)
                ->where('sort_order', '>', $deletedOrder)
                ->decrement('sort_order');
        });

        return response()->json(['message' => 'Курс успішно видалено'], 200);
    }
}
