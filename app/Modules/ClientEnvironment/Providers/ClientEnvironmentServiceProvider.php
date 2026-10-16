<?php

namespace App\Modules\ClientEnvironment\Providers;

use Illuminate\Support\ServiceProvider;

class ClientEnvironmentServiceProvider extends ServiceProvider
{
    /**
     * Реєстрація внутрішніх сервісів модуля.
     */
    public function register(): void
    {
        // Залишаємо пустим, за потреби зареєструємо сервіс динамічної JSON-валідації
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
