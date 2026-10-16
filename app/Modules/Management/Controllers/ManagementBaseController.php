<?php

namespace App\Modules\Management\Controllers;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Панель Керівництва / Контроль Виробництва",
    description: "Інструменти вищого керівництва (Admin) для постановки макро-завдань, аудиту дедлайнів та аналітики студії"
)]
#[OA\Tag(
    name: "Кабінет Менеджера / Завдання",
    description: "Ізольоване робоче середовище для Project Manager-ів (PM) для отримання та зміни статусів завдань"
)]
class ManagementBaseController extends Controller
{
    // Базовий клас для наслідування контролерами модуля Management
}
