<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції таблиці проєктів з підтримкою деревоподібних етапів.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            
            // Рекурсивний зв'язок: посилання на цю ж таблицю projects (nullable)
            // При видаленні головного контракту автоматично видаляються всі його етапи
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('projects')
                ->onDelete('cascade');

            // Зовнішній ключ на користувача системи (клієнта з роллю client)
            $table->foreignId('client_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Зовнішній ключ на конкретний шаблон брифу (nullable, лише для дочірніх етапів)
            // База даних заблокує видалення шаблону, якщо до нього прив'язаний хоча б один проєкт
            $table->foreignId('brief_template_id')
                ->nullable()
                ->constrained('brief_templates')
                ->onRestrictDelete();

            $table->string('title'); // Назва проєкту або етапу
            $table->string('status'); // Робочий стан: briefing, ready_for_sign, in_development, completed
            $table->jsonb('brief_data')->nullable(); // Чисті відповіді клієнта (ключ: значення) у JSONB
            $table->integer('budget')->default(0); // Бюджет (зберігається в копійках або мінімальних одиницях валюти)
            $table->timestamps();

            // Індекси для оптимізації деревоподібних запитів та миттєвого завантаження мультипроєктного дашборду
            $table->index('parent_id');
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
