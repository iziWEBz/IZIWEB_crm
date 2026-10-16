<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Management\Controllers\AdminManagementController;
use App\Modules\Management\Controllers\PMTaskController;

Route::group([], function () {

    // 1. ГРУПА: Панель Керівництва (Тільки для Супер-Адміна)
    Route::prefix('management')->middleware(['role:admin'])->group(function () {
        Route::post('/tasks', [AdminManagementController::class, 'store']); // Створення таски
        Route::get('/tasks/list', [AdminManagementController::class, 'index']); // Перегляд усіх тасок + фільтр ?pm_id=X
        Route::put('/tasks/{id}/update', [AdminManagementController::class, 'update']); // Повне редагування
        Route::patch('/tasks/{id}/approve', [AdminManagementController::class, 'approve']); // Схвалення (completed)
        Route::delete('/tasks/{id}', [AdminManagementController::class, 'destroy']); // Видалення (захист статусів)
        Route::get('/dashboard/metrics', [AdminManagementController::class, 'dashboardMetrics']); // Бізнес-аналітика
    });

    // 2. ГРУПА: Кабінет Менеджера (Для ролі PM та Admin)
    Route::prefix('pm')->middleware(['role:admin,pm'])->group(function () {
        Route::get('/tasks', [PMTaskController::class, 'myTasks']); // Список особистих тасок
        Route::patch('/tasks/{id}/status', [PMTaskController::class, 'updateStatus']); // Відправка на review з result_url
    });

});
