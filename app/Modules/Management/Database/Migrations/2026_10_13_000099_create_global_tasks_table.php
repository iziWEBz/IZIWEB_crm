<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції таблиці глобальних завдань Керівництва.
     */
    public function up(): void
    {
        Schema::create('global_tasks', function (Blueprint $table) {
            $table->id();
            
            // Зв'язок із проєктом клієнта (при видаленні проєкту завдання чистяться каскадно)
            $table->foreignId('project_id')
                ->constrained('projects')
                ->onDelete('cascade');

            // Зв'язок із відповідальним PM-ом (користувач із таблиці users)
            $table->foreignId('pm_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('title'); // Коротка назва макро-завдання
            $table->text('description'); // Детальний опис кроку та вимог Керівництва
            $table->string('status'); // pending, in_progress, review, completed
            $table->string('result_url')->nullable(); // Посилання на договір / Figma / файл звіту
            $table->timestamp('deadline_at'); // Обов'язковий дедлайн
            $table->timestamps();

            // Композитний індекс для миттєвого завантаження тасок конкретного PM-а з фільтром за статусом
            $table->index(['pm_id', 'status']);
            // Індекс для швидкого підсвічування протермінованих дедлайнів на дашборді метрик
            $table->index('deadline_at');
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_tasks');
    }
};
