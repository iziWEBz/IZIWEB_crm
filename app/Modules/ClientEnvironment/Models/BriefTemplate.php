<?php

namespace App\Modules\ClientEnvironment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BriefTemplate extends Model
{
    // Явно вказуємо назву таблиці в PostgreSQL
    protected $table = 'brief_templates';

    // Поля для масового заповнення
    protected $fillable = [
        'name',
        'service_type',
        'schema_payload',
    ];

    // Автоматичне приведення типів
    protected $casts = [
        'schema_payload' => 'array', // Конвертує JSONB схему конструктора у масив PHP
    ];

    /**
     * Зв'язок «Один до багатьох» із проєктами компанії.
     * Один шаблон брифу може використовуватися у безлічі різних проєктів/етапів.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'brief_template_id', 'id');
    }
}
