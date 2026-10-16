<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Запуск міграції таблиці модерованих тікетів.
     */
    public function up(): void
    {
        Schema::create('communication_tickets', function (Blueprint $table) {
            $table->id();
            // Зв'язок із проєктом або етапом (при видаленні проєкту тікети очищаються каскадно)
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            // Автор тікета (користувач, який його створив)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('title'); // Тема звернення
            $table->string('category'); // Фіксована категорія: tech_issue, documents, edits
            $table->string('status'); // Стан тікета: open, in_progress, waiting_client, resolved
            
            $table->text('message_from_specialist')->nullable(); // Внутрішній сирий текст від команди
            $table->text('message_to_client')->nullable(); // Відмодерований PM-ом текст для кабінету клієнта
            $table->timestamps();

            // Композитний індекс для прискорення сортування та фільтрації тікетів менеджером за категоріями
            $table->index(['project_id', 'category']);
        });
    }

    /**
     * Скасування міграції.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_tickets');
    }
};
