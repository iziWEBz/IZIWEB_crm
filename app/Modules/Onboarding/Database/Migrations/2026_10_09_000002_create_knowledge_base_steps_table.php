<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції.
     */
    public function up(): void
    {
        Schema::create('knowledge_base_steps', function (Blueprint $table) {
            $table->id();
            // Зовнішній ключ із каскадним видаленням при видаленні курсу
            $table->foreignId('course_id')->constrained('onboarding_courses')->onDelete('cascade');
            $table->string('title'); // Назва конкретного завдання/лекції
            $table->longText('content'); // Лонгтекст теорії, інструкцій або посилань
            $table->integer('sort_order'); // Порядковий номер завдання всередині курсу
            $table->boolean('is_active')->default(true); // Прапорець деактивації кроку
            $table->jsonb('test_schema')->nullable(); // Схема тесту з правильними відповідями (PostgreSQL JSONB)
            $table->timestamps();

            // Індекс для прискорення завантаження лінійного списку кроків конкретного курсу
            $table->index(['course_id', 'sort_order']);
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_steps');
    }
};
