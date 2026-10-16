<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ClientEnvironment\Controllers\AdminProjectController;
use App\Modules\ClientEnvironment\Controllers\AdminBriefTemplateController;
use App\Modules\ClientEnvironment\Controllers\AdminTicketController;
use App\Modules\ClientEnvironment\Controllers\ClientCabinetController;

Route::group([], function () {
    
    // 1. ГРУПА: client-env (Проєкти та Бріфи для Admin/PM)
    Route::prefix('client-env')->middleware(['role:admin,pm'])->group(function () {
        // Проєкти
        Route::get('/projects', [AdminProjectController::class, 'index']);
        Route::post('/projects/store', [AdminProjectController::class, 'store']);
        Route::put('/projects/{id}/update', [AdminProjectController::class, 'update']);
        Route::patch('/projects/{id}/status', [AdminProjectController::class, 'updateStatus']);
        Route::delete('/projects/{id}', [AdminProjectController::class, 'destroy']);


        // Шаблони брифів (Конструктор полів)
        Route::get('/brief-templates', [AdminBriefTemplateController::class, 'index']);
        Route::post('/brief-templates/store', [AdminBriefTemplateController::class, 'store']);
        Route::put('/brief-templates/{id}/update', [AdminBriefTemplateController::class, 'update']);
        Route::patch('/brief-templates/{id}/fields', [AdminBriefTemplateController::class, 'updateFields']);
        Route::delete('/brief-templates/{id}', [AdminBriefTemplateController::class, 'destroy']);


    });

    // 2. ГРУПА: pm (Шлюз модерації тікетів для Admin/PM)
    Route::prefix('pm')->middleware(['role:admin,pm'])->group(function () {
        Route::get('/tickets/moderation', [AdminTicketController::class, 'index']);
        Route::patch('/tickets/{id}/approve', [AdminTicketController::class, 'approve']);
        Route::post('/tickets/{id}/reply', [AdminTicketController::class, 'replyToTicket']);
        Route::post('/tickets/store-for-client', [AdminTicketController::class, 'storeTicketForClient']);
    });

        // 3. ГРУПА: client (Мультипроєктний кабінет замовника з Project Boundary)
    Route::prefix('client')->middleware(['role:admin,client'])->group(function () {
        Route::get('/projects', [ClientCabinetController::class, 'myProjects']);
        Route::get('/projects/{id}/brief/template', [ClientCabinetController::class, 'getBriefTemplate']);
        Route::post('/projects/{id}/brief/step', [ClientCabinetController::class, 'saveBriefStep']);
        Route::post('/projects/{id}/contract/sign', [ClientCabinetController::class, 'signContract']);
        Route::get('/projects/{id}/tickets', [ClientCabinetController::class, 'getTickets']);
        Route::post('/projects/{id}/tickets/store', [ClientCabinetController::class, 'storeTicket']);
    });

});
