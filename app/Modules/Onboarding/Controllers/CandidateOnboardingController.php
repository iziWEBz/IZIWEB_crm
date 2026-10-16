<?php

namespace App\Modules\Onboarding\Controllers;

use App\Modules\Onboarding\Models\OnboardingCourse;
use App\Modules\Onboarding\Models\KnowledgeBaseStep;
use App\Modules\Onboarding\Models\CandidateProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CandidateOnboardingController extends OnboardingBaseController
{
    #[OA\Get(
        path: '/onboarding/my-courses',
        summary: 'Список активних курсів стажера за його спеціалізацією з розрахунком прогресу (або всі курси для Admin/PM)',
        tags: ['Кабінет Стажера / Проходження'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успішне отримання курсів із прогресом',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
            new OA\Response(response: 422, description: 'Помилка валідації профілю стажера')
        ]
    )]
    public function myCourses(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. ПЕРЕВІРКА НА МЕНЕДЖЕРА/АДМІНІСТРАТОРА
        // Якщо заходить admin або pm — віддаємо ВСІ активні курси студії для аудиту прогресу
        if ($user->hasRole('admin') || $user->hasRole('pm')) {
            $courses = OnboardingCourse::where('is_active', true)
                ->orderBy('specialization')
                ->orderBy('sort_order', 'asc')
                ->get();

            // Для керівництва прогрес залишаємо 100% або 0%, оскільки вони просто переглядають програму
            $coursesWithProgress = $courses->map(function ($course) {
                $course->progress_percent = 0;
                return $course;
            });

            return response()->json($coursesWithProgress, 200);
        }

        // 2. ЛОГІКА ДЛЯ ЗВИЧАЙНОГО СТАЖЕРА
        $specialization = $user->profile_data['specialization'] ?? null;

        if (!$specialization) {
            return response()->json(['message' => 'Спеціалізацію стажера не вказано в профілі.'], 422);
        }

        $courses = OnboardingCourse::where('specialization', $specialization)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $coursesWithProgress = $courses->map(function ($course) use ($user) {
            $totalSteps = $course->steps()->where('is_active', true)->count();

            if ($totalSteps === 0) {
                $course->progress_percent = 0;
                return $course;
            }

            $passedSteps = CandidateProgress::where('user_id', $user->id)
                ->whereIn('step_id', $course->steps()->where('is_active', true)->pluck('id'))
                ->where('status', 'passed')
                ->count();

            $course->progress_percent = (int) round(($passedSteps / $totalSteps) * 100);

            return $course;
        });

        return response()->json($coursesWithProgress, 200);
    }


    #[OA\Get(
        path: '/onboarding/courses/{course_id}/my-tasks',
        summary: 'Лінійний список завдань курсу з динамічними статусами проходження',
        tags: ['Кабінет Стажера / Проходження'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'course_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне отримання завдань зі статусами'),
            new OA\Response(response: 404, description: 'Курс не знайдено')
        ]
    )]
    public function myTasks(int $courseId, Request $request): JsonResponse
    {
        $user = $request->user();
        $course = OnboardingCourse::where('id', $courseId)->where('is_active', true)->firstOrFail();

        // Отримуємо всі активні кроки курсу
        $steps = $course->steps()->where('is_active', true)->get();

        // Дістаємо весь поточний прогрес стажера по цьому курсу за один запит (оптимізація)
        $progressMap = CandidateProgress::where('user_id', $user->id)
            ->whereIn('step_id', $steps->pluck('id'))
            ->get()
            ->keyBy('step_id');

        $isPreviousPassed = true; // Прапорець для відстеження послідовності доступу

        $stepsWithStatuses = $steps->map(function ($step) use ($progressMap, &$isPreviousPassed) {
            $progress = $progressMap->get($step->id);
            
            // Якщо запис у прогресі є, виводимо його статус (passed, review, in_progress)
            if ($progress) {
                $step->candidate_status = $progress->status;
                $isPreviousPassed = ($progress->status === 'passed');
            } else {
                // Якщо запису немає, але попередній крок здано — цей стає доступним ('in_progress')
                if ($isPreviousPassed) {
                    $step->candidate_status = 'in_progress';
                    $isPreviousPassed = false; // Наступні кроки мають бути заблоковані
                } else {
                    // Якщо попередній крок ще не здано — завдання жорстко заблоковане
                    $step->candidate_status = 'locked';
                }
            }

            // Приховуємо конфіденційні поля теорії та тестів для загального списку завдань
            unset($step->content);
            unset($step->test_schema);

            return $step;
        });

        return response()->json($stepsWithStatuses, 200);
    }

    #[OA\Get(
        path: '/onboarding/steps/{id}',
        summary: 'Відкриття тексту лекції та тестових питань із маскуванням відповідей',
        tags: ['Кабінет Стажера / Проходження'], 
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Успішне відкриття кроку'),
            new OA\Response(response: 403, description: 'Доступ заблоковано'),
            new OA\Response(response: 404, description: 'Завдання не знайдено')
        ]
    )]
    public function showStep(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Шукаємо активний крок
        $step = KnowledgeBaseStep::where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        // 1. ПЕРЕВІРКА ДОСТУПУ (Чи закриті попередні кроки)
        // Отримуємо попередній активний крок у цьому ж курсі
        $previousStep = KnowledgeBaseStep::where('course_id', $step->course_id)
            ->where('is_active', true)
            ->where('sort_order', '<', $step->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previousStep) {
            // Перевіряємо, чи є статус 'passed' у стажера для попереднього кроку
            $isPassed = CandidateProgress::where('user_id', $user->id)
                ->where('step_id', $previousStep->id)
                ->where('status', 'passed')
                ->exists();

            if (!$isPassed) {
                return response()->json(['message' => 'Доступ заблоковано. Спочатку пройдіть попередні кроки.'], 403);
            }
        }

        // 2. МАСКУВАННЯ ПРАВИЛЬНИХ ВІДПОВІДЕЙ ТЕСТУ
        if ($step->test_schema && is_array($step->test_schema)) {
            $maskedSchema = array_map(function ($question) {
                // Видаляємо конфіденційний індекс еталонної відповіді перед відправкою клієнту
                unset($question['correct_option_index']);
                return $question;
            }, $step->test_schema);

            $step->test_schema = $maskedSchema;
        }

        // Ініціалізуємо статус 'in_progress' в прогресі, якщо стажер відкрив крок вперше
        CandidateProgress::firstOrCreate(
            ['user_id' => $user->id, 'step_id' => $step->id],
            ['status' => 'in_progress']
        );

        return response()->json($step, 200);
    }

    #[OA\Post(
        path: '/onboarding/steps/{id}/submit',
        summary: 'Відправка відповідей на тест або посилання на практичне завдання',
        tags: ['Кабінет Стажера / Проходження'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'answers', type: 'object', example: ["1" => 1]),
                    new OA\Property(property: 'task_url', type: 'string', format: 'uri', example: 'https://github.com')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Результат обробки завдання'),
            new OA\Response(response: 422, description: 'Помилка валідації вхідних даних')
        ]
    )]

    public function submitStep(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $step = KnowledgeBaseStep::where('id', $id)->where('is_active', true)->firstOrFail();

        $progress = CandidateProgress::where('user_id', $user->id)
            ->where('step_id', $step->id)
            ->firstOrFail();

        if ($progress->status === 'passed') {
            return response()->json(['message' => 'Цей крок уже успішно здано.'], 422);
        }

        // ВАРІАНТ 1: Якщо це практичне завдання (передано посилання)
        if ($request->has('task_url') && !empty($request->input('task_url'))) {
            $request->validate(['task_url' => ['url', 'max:255']]);

            $progress->update([
                'task_url' => $request->input('task_url'),
                'status' => 'review' // Переводимо на перевірку PM-у
            ]);

            return response()->json(['message' => 'Практичну роботу успішно надіслано на перевірку.', 'status' => 'review'], 200);
        }

        // ВАРІАНТ 2: Якщо це автоматичний тест
        if ($request->has('answers')) {
            $request->validate(['answers' => ['required', 'array']]);
            $candidateAnswers = $request->input('answers');
            $realSchema = $step->test_schema;

            if (!$realSchema || !is_array($realSchema)) {
                return response()->json(['message' => 'Для цього кроку немає налаштованого тесту.'], 422);
            }

            $isAllCorrect = true;
            $logAnswers = [];

            // Звіряємо кожне питання з оригінальною схемою з Бази Даних
            foreach ($realSchema as $question) {
                $qId = $question['question_id'];
                $correctIndex = $question['correct_option_index'];
                $selectedIndex = $candidateAnswers[$qId] ?? null;

                $isCorrect = ($selectedIndex !== null && (int)$selectedIndex === (int)$correctIndex);
                
                if (!$isCorrect) {
                    $isAllCorrect = false;
                }

                $logAnswers[] = [
                    'question_id' => $qId,
                    'selected_option' => $selectedIndex,
                    'is_correct' => $isCorrect
                ];
            }

            // Оновлюємо прогрес та лог відповідей у базі
            $status = $isAllCorrect ? 'passed' : 'in_progress';
            $progress->update([
                'status' => $status,
                'test_answers' => $logAnswers
            ]);

            if ($isAllCorrect) {
                return response()->json(['message' => 'Тест здано успішно! Доступ до наступного кроку відкрито.', 'status' => 'passed'], 200);
            }

            return response()->json(['message' => 'Тест не пройдено. Є помилки. Спробуйте ще раз.', 'status' => 'in_progress', 'details' => $logAnswers], 200);
        }

        return response()->json(['message' => 'Необхідно надати відповіді на тест або посилання на практичне завдання.'], 422);
    }

}
