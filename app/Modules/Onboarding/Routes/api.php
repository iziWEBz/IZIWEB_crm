<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Onboarding\Controllers\AdminCourseController;
use App\Modules\Onboarding\Controllers\AdminStepController;
use App\Modules\Onboarding\Controllers\CandidateOnboardingController;
use App\Modules\Onboarding\Controllers\AdminReviewController;

Route::prefix('onboarding')->group(function () {

    // ГРУПА А: Панель Управління та Перевірки Робіт (Доступно для Керівництва та PM-ів)
    // Явно вказуємо роль admin поруч із pm
    Route::middleware(['role:admin,pm'])->group(function () {
        // Рівень 1: Керування Курсами
        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::post('/courses/store', [AdminCourseController::class, 'store']);
        Route::put('/courses/{id}/update', [AdminCourseController::class, 'update']);
        Route::patch('/courses/{id}/toggle-status', [AdminCourseController::class, 'toggleStatus']);
        Route::post('/courses/reorder', [AdminCourseController::class, 'reorder']);
        Route::delete('/courses/{id}', [AdminCourseController::class, 'destroy']);

        // Рівень 2: Керування Завданнями / Кроками
        Route::get('/courses/{course_id}/steps', [AdminStepController::class, 'indexSteps']);
        Route::post('/steps/store', [AdminStepController::class, 'storeStep']);
        Route::put('/steps/{id}/update', [AdminStepController::class, 'updateStep']);
        Route::patch('/steps/{id}/toggle-status', [AdminStepController::class, 'toggleStatusStep']);
        Route::post('/steps/reorder', [AdminStepController::class, 'reorderStep']);
        Route::delete('/steps/{id}', [AdminStepController::class, 'destroyStep']);

        // Контур перевірки практичних робіт Ментором
        Route::get('/reviews', [AdminReviewController::class, 'indexReviews']);
        Route::patch('/reviews/{id}/verify', [AdminReviewController::class, 'verifyReview']);
    });

    // ГРУПА Б: Кабінет Стажера (Доступно для кандидатів, лінійних спеціалістів, а також для адміна для аудиту)
    // Додаємо admin першим аргументом
    Route::middleware(['role:admin,candidate,specialist'])->group(function () {
        Route::get('/my-courses', [CandidateOnboardingController::class, 'myCourses']);
        Route::get('/courses/{course_id}/my-tasks', [CandidateOnboardingController::class, 'myTasks']);
        Route::get('/steps/{id}', [CandidateOnboardingController::class, 'showStep']);
        Route::post('/steps/{id}/submit', [CandidateOnboardingController::class, 'submitStep']);
    });

});
