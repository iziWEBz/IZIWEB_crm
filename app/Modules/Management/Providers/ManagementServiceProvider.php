<?php

namespace App\Modules\Management\Providers;

use Illuminate\Support\ServiceProvider;

class ManagementServiceProvider extends ServiceProvider
{
    /**
     * Реєстрація внутрішніх сервісів модуля.
     */
    public function register(): void
    {
        // Місце для майбутньої реєстрації сервісів або репозиторіїв
    }

    /**
     * Завантаження ресурсів модуля (міграцій).
     */
    public function boot(): void
    {
        // Автоматично вказуємо ядру Laravel, де шукати міграції цього модуля
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
