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
        Schema::create('onboarding_courses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Назва курсу
            $table->text('description'); // Детальний опис програми
            $table->string('specialization'); // Маркер напрямку (backend, frontend, design, seo)
            $table->integer('sort_order'); // Порядковий номер курсу
            $table->boolean('is_active')->default(true); // Прапорець активності курсу
            $table->timestamps();

            // Індекс для оптимізації фільтрації за напрямком та швидкого сортування
            $table->index(['specialization', 'sort_order']);
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_courses');
    }
};
