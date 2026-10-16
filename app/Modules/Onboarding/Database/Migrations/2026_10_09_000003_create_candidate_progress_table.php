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
        Schema::create('candidate_progress', function (Blueprint $table) {
            $table->id();
            // Зв'язок із користувачем (таблиця users з модуля IAM)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Зв'язок із конкретним кроком курсу
            $table->foreignId('step_id')->constrained('knowledge_base_steps')->onDelete('cascade');
            $table->string('status'); // Поточний стан завдання: in_progress, passed, review
            $table->jsonb('test_answers')->nullable(); // Масив відповідей стажера для логування та аналітики
            $table->string('task_url')->nullable(); // Посилання на Git/Figma для тасок із практичною перевіркою
            $table->timestamps();

            // Унікальний композитний індекс: один юзер проходить один крок строго один раз
            $table->unique(['user_id', 'step_id']);
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_progress');
    }
};
