<?php

namespace App\Modules\ClientEnvironment\Controllers;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Панель Менеджера / Клієнтський Контур",
    description: "Адміністративні інструменти PM-а та Керівництва для управління деревом проєктів та конструктором брифів"
)]
#[OA\Tag(
    name: "Кабінет Клієнта / Інтерфейс",
    description: "Ізольоване мультипроєктне середовище для замовників (бріфи, цифрові підписи, тікети)"
)]
class ClientEnvBaseController extends Controller
{
    // Базовий клас для наслідування контролерами модуля ClientEnvironment
}
