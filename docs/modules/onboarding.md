# Модуль Onboarding (Контур №1: Навчання та База знань)

## 1. Загальний опис модуля
Модуль повністю автоматизує процес навчання та первинного відбору кандидатів (стажерів) за чотирма напрямками: Дизайн, Фронтенд, Бекенд та SEO. Головна мета — накопичувати технічні знання всередині студії та відсіювати немотивованих стажерів без залучення діючих співробітників компанії.

---

## 2. Структура каталогів модуля (SOLID / DDD-lite)

Для запобігання утворенню «божественних класів» (God Objects) адмін-контур та кабінети розділені на окремі вузькоспеціалізовані контролери:

```text
app/Modules/Onboarding/
├── Controllers/
│   ├── OnboardingBaseController.php    # Базовий контролер модуля (OpenAPI теги)
│   ├── AdminCourseController.php       # Управління Курсами (Рівень 1 CRUD, Drag-and-Drop)
│   ├── AdminStepController.php         # Управління Завданнями та Тестами (Рівень 2 CRUD, JSONB)
│   ├── AdminReviewController.php       # Панель Ментора (Перевірка практичних тасок стажерів)
│   └── CandidateOnboardingController.php # Кабінет Стажера (Лінійне проходження, маскування тестів)
├── Database/
│   ├── Migrations/
│   │   ├── 2026_10_09_000001_create_onboarding_courses_table.php
│   │   ├── 2026_10_09_000002_create_knowledge_base_steps_table.php
│   │   └── 2026_10_09_000003_create_candidate_progress_table.php
│   └── Seeders/
│       ├── OnboardingDatabaseSeeder.php # Головний збірковий сідер (очищення та виклик доменних)
│       ├── BackendCourseSeeder.php     # Доменне наповнення курсами та тестами для Backend
│       ├── FrontendCourseSeeder.php    # Доменне наповнення курсами та тестами для Frontend
│       ├── DesignCourseSeeder.php      # Доменне наповнення курсами та тестами для UI/UX Design
│       └── SeoCourseSeeder.php         # Доменне наповнення курсами та тестами для SEO
├── Models/
│   ├── OnboardingCourse.php            # Модель курсу (hasMany steps з автосортуванням ASC)
│   ├── KnowledgeBaseStep.php           # Модель лекції/тесту (casts: test_schema -> array)
│   └── CandidateProgress.php           # Модель прогресу (casts: test_answers -> array)
├── Providers/
│   └── OnboardingServiceProvider.php   # Автономне завантаження міграцій домену
└── Routes/
    └── api.php                        # Ізольовані маршрути контурів під захистом мідлвару role
```

---

## 3. Глобальна модель даних (СУБД PostgreSQL)

### 3.1. Структура питань тесту (Таблиця `knowledge_base_steps`, поле `test_schema`)
Конфіденційний еталон правильних відповідей зберігається строго на сервері у форматі `JSONB` і ніколи не віддається стажеру у вихідному JSON.

```json
[
  {
    "question_id": 1,
    "question_text": "Який HTTP-метод використовується для створення ресурсів у REST API?",
    "options": ["GET", "POST", "PUT", "DELETE"],
    "correct_option_index": 1, 
    "type": "single_choice"
  }
]
```

### 3.2. Структура логів відповідей (Таблиця `candidate_progress`, pole `test_answers`)
Автоматично розраховується бекендом під час перевірки та зберігає фінальний результат спроби.

```json
[
  {
    "question_id": 1,
    "selected_option": 1,
    "is_correct": true
  }
]
```

---

## 4. Бізнес-логіка та бізнес-правила контуру

### 4.1. Логіка послідовних переходів (Контрольні точки)
*   Маршрут навчання складається з курсів та завдань (`sort_order ASC`).
*   Доступ до наступного кроку жорстко закритий на рівні бекенду (`403 Forbidden`), поки попередній активний крок у курсі (або останнє завдання попереднього активного курсу вашої спеціалізації) не переведено в статус `passed`.
*   Валідація кроку буває двох типів:
    1.  **Автоматична (Тести):** Бекенд порівнює надіслані стажером індекси з прихованими `correct_option_index` у базі даних. У разі 100% збігу крок автоматично отримує статус `passed`, відкриваючи наступний.
    2.  **Ручна (Практичні завдання):** Стажер надсилає лінк на Figma або Git. Крок переходить у статус `review` і блокує лінійне проходження. Ментор вручну переводить таску в `passed` або повертає в `in_progress` із текстовим фідбеком.

### 4.2. Рольова модель та ізоляція контурів (`CheckRole`)
Доступ до ендпоінтів контролюється за допомогою кастомного Middleware `role` (`App\Http\Middleware\CheckRole`), який підтримує мультиролі (`...$roles`).
*   **Панель управління та перевірки:** Доступно строго для ролей `admin, pm`.
*   **Кабінет стажера:** Доступно для ролей `admin, candidate, specialist`. Роль `admin` (Керівництво) має повний безлімітний доступ до всіх контурів за замовчуванням.

---

## 5. Специфікація API-ендпоінтів та Інтеграція зі Swagger (Sanctum)

Усі захищені ендпоінти вимагають передачі Bearer токена у схемі авторизації `security: [['sanctum' => []]]`. У документації методи розділені на три незалежні вкладки.

### Вкладка 1: Модуль Onboarding / Навчання (Панель Управління)
*   `GET /api/v1/onboarding/courses` — Список усіх курсів із фільтрацією за `specialization` (`sort_order ASC`).
*   `POST /api/v1/onboarding/courses/store` — Створення курсу з авторозрахунком сортування (`MAX + 1`).
*   `PUT /api/v1/onboarding/courses/{id}/update` — Редагування текстових полів курсу.
*   `PATCH /api/v1/onboarding/courses/{id}/toggle-status` — Швидкий тоггл активності `is_active` курсу.
*   `POST /api/v1/onboarding/courses/reorder` — Транзакційний Drag-and-Drop перерахунок позицій курсів.
*   `DELETE /api/v1/onboarding/courses/{id}` — Видалення курсу з каскадним зсувом номерів, що залишилися.
*   `GET /api/v1/onboarding/courses/{course_id}/steps` — Список усіх завдань курсу (`sort_order ASC`).
*   `POST /api/v1/onboarding/steps/store` — Додавання нового кроку/тесту з авторозрахунком сортування всередині курсу.
*   `PUT /api/v1/onboarding/steps/{id}/update` — Редагування лекції та JSONB-схеми тесту.
*   `PATCH /api/v1/onboarding/steps/{id}/toggle-status` — Швидкий тоггл активності конкретного завдання.
*   `POST /api/v1/onboarding/steps/reorder` — Транзакційне Drag-and-Drop пересортування кроків всередині курсу.
*   `DELETE /api/v1/onboarding/steps/{id}` — Видалення завдання з автоматичним перерахунком порядкових номерів.

### Вкладка 2: Кабінет Стажера / Проходження (Ізольований Контур)
*   `GET /api/v1/onboarding/my-courses` — Список курсів стажера за напрямком із динамічним розрахунком відсотка прогресу.
*   `GET /api/v1/onboarding/courses/{course_id}/my-tasks` — Лінійний список завдань зі статусами (`locked`, `in_progress`, `review`, `passed`).
*   `GET /api/v1/onboarding/steps/{id}` — Відкриття тексту лекції. Бекенд **примусово вирізає ключ `correct_option_index`** з JSONB тесту для безпеки. Діє блок (403), якщо попередній крок не здано.
*   `POST /api/v1/onboarding/steps/{id}/submit` — Відправка відповідей на тест (автоперевірка з виставленням `passed`) або фіксація практичного `task_url` (переведення в статус `review`).

### Вкладка 3: Панель Ментора / Перевірка Робіт (Ручний Контур)
*   `GET /api/v1/onboarding/reviews` — Пул практичних робіт усіх стажерів у статусі `review` з eager loading зв'язків.
*   `PATCH /api/v1/onboarding/reviews/{id}/verify` — Вердикт ментора: затвердити (`passed`) або відхилити (`in_progress` із зануленням `task_url`).
