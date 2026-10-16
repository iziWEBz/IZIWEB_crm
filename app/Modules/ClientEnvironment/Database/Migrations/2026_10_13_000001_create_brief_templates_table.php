<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції таблиці шаблонів брифів.
     */
    public function up(): void
    {
        Schema::create('brief_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Назва шаблону (наприклад: "Бриф: Інтернет-магазин")
            $table->string('service_type'); // Напрямок для групування (web_development, seo, design)
            $table->jsonb('schema_payload'); // Структура динамічних кроків та специфікація полів брифу
            $table->timestamps();

            // Індекс для швидкого групування або фільтрації шаблонів за типом послуги
            $table->index('service_type');
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('brief_templates');
    }
};
