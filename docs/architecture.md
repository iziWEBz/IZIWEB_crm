# Загальна архітектура CRM-середовища (Internal Environment)

## 1. Головні інженерні принципи системи
*   **Headless (Pure REST API):** Бекенд повністю ізольований від фронтенду. Взаємодія відбувається виключно через стандартизоване REST API.
*   **Модульний моноліт (DDD-lite):** Код розділений на незалежні доменні модулі всередині однієї кодової бази (`app/Modules/`). Кожен модуль ізольований і має власні маршрути, контролери, моделі та міграції.
*   **Ізольована безпека (Zero-Email Trust):** Авторизація строго за парою `Логін / Пароль`. Система не використовує відкриті посилання через Email для критичних дій.

## 2. Загальна структура папок проєкту
Вся бізнес-логіка розширює стандартну структуру Laravel і переноситься в директорію `app/Modules/`:
```text
app/Modules/
├── IAM/                # Ідентифікація та контроль доступу (Див. docs/modules/iam.md)
├── Management/         # Модуль Керівництва (Див. docs/modules/management.md)
├── Onboarding/         # Контур №1: Навчання та стажери (Див. docs/modules/onboarding.md)
└── ClientEnvironment/  # Контур №2: Клієнти, Брифи, Тікети (Див. docs/modules/client_environment.md)
```

## 3. Глобальна модель даних (СУБД PostgreSQL)
Для забезпечення транзакційності та гнучкості використовується PostgreSQL. Складноструктуровані дані (схеми брифів, відповіді, тести, логи) зберігаються у полях типу `JSONB`.

### Загальний перелік сутностей (Таблиць):
*   **IAM:** `users`, ролі та пермішени (пакет `Spatie Laravel-Permission`).
*   **Management:** `global_tasks`, `manager_reports`.
*   **Onboarding:** `knowledge_base_steps`, `candidate_progress`.
*   **ClientEnvironment:** `projects`, `brief_templates`, `contract_signatures`, `communication_tickets`.

## 4. Правила модульного роутингу (Laravel 11)
Фреймворк конфігурується без використання стандартного корневого файлу `routes/api.php`. Усі маршрути реєструються динамічно в ядрі програми через файл `bootstrap/app.php` шляхом прямого підключення файлів маршрутів конкретного модуля:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        // Реєстрація ізольованих роутів модуля IAM
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(base_path('app/Modules/IAM/Routes/api.php'));
    },
)
```

## 5. Інструменти розробки, Мережа та Автодокументація
*   **Автентифікація:** **Laravel Sanctum** (Stateful/Token-based API). Трейт `HasApiTokens` має бути обов'язково підключений у моделі `User`.
*   **Локальний порт системи:** Локальне оточення NGINX у Docker зафіксовано на порту **`8090`** (`http://localhost:8090`).
*   Усі запити приймають та повертають дані виключно у форматі `application/json`. Інтерактивний Swagger UI розгорнуто за адресою `http://localhost:8090/api/documentation`.
*   **Документація API:** **L5-Swagger (OpenAPI v3)**. 
    *   **Синтаксис:** Для забезпечення 100% сумісності з парсером у Laravel 11 документація ведеться строго через **Атрибути PHP 8** (використання застарілих коментарів DocBlock `@OA` заборонено, оскільки вони ігноруються внутрішнім валідатором).
    *   **Аналізатор:** У файлі `config/l5-swagger.php` увімкнено сучасний текстовий `TokenAnalyser` (`'analyser' => null` за замовчуванням за умови встановленого пакета `doctrine/annotations`), який рекурсивно сканує виключно директорію `app/Modules`.
