<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції таблиці логування цифрової згоди.
     */
    public function up(): void
    {
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            // Каскадне видалення логу підпису, якщо сам проєкт/контракт видаляється супер-адміном
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('document_hash'); // Криптографічний хеш тексту договору (SHA-256)
            $table->string('ip_address'); // IP-адреса клієнта для логу безпеки
            $table->text('user_agent'); // Браузер та операційна система клієнта
            $table->timestamp('signed_at'); // Точний час фіксації кліку підписання
            $table->timestamps();

            // Індекс для швидкого пошуку або аудиту всіх підписаних договорів конкретного проєкту
            $table->index('project_id');
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
    }
};
