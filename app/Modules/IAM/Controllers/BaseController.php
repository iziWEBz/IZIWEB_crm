<?php

namespace App\Modules\IAM\Controllers;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: "1.0.0",
        title: "Веб-студія CRM API",
        description: "Документація модульного Headless бекенду CRM-системи"
    ),
    servers: [
        new OA\Server(url: "http://localhost:8090/api/v1", description: "Локальний сервер розробки (Docker)")
    ],
    tags: [
        new OA\Tag(name: "Автентифікація", description: "Контур авторизації, реєстрації та керування токенами користувачів"),
        new OA\Tag(name: "Управління користувачами", description: "Управління користувачами"),
        new OA\Tag(name: "Модуль Onboarding / Навчання", description: "Адміністративний контур та кабінет стажера для управління базою знань і тестами"),
        new OA\Tag(name: "Кабінет Стажера / Проходження", description: "Кабінет Стажера / Проходження"),
        new OA\Tag(name: "Панель Ментора / Перевірка Робіт", description: "Панель Ментора / Перевірка Робіт"),
        new OA\Tag(name: "Панель Керівництва / Контроль Виробництва", description: "Інструменти вищого керівництва (Admin) для постановки макро-завдань, аудиту дедлайнів та аналітики студії"),
        new OA\Tag(name: "Панель Менеджера / Клієнтський Контур", description: "Адміністративні інструменти PM-а та Керівництва для управління деревом проєктів та конструктором брифів"),
        new OA\Tag(name: "Кабінет Клієнта / Інтерфейс", description: "Ізольоване мультипроєктне середовище для замовників (бріфи, цифрові підписи, тікети)"),
        new OA\Tag(name: "Панель Ментора / Модерація Тікетів", description: "Панель Ментора / Модерація Тікетів")
    ]
) ]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Введіть отриманий access_token для доступу до захищених ендпоінтів"
)]


abstract class BaseController extends Controller
{

}
