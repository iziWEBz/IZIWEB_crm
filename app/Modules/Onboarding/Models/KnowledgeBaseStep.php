<?php

namespace App\Modules\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseStep extends Model
{
    // Явно вказуємо назву таблиці в СУБД
    protected $table = 'knowledge_base_steps';

    // Дозволені поля для масового заповнення
    protected $fillable = [
        'course_id',
        'title',
        'content',
        'sort_order',
        'is_active',
        'test_schema',
    ];

    // Автоматичне приведення типів
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'test_schema' => 'array', // Конвертує PostgreSQL JSONB у чистий масив PHP
    ];

    /**
     * Зворотний зв'язок «Багато до одного» з курсом навчання.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(OnboardingCourse::class, 'course_id', 'id');
    }

    /**
     * Зв'язок «Один до багатьох» з логами прогресу стажерів.
     */
    public function progresses(): HasMany
    {
        return $this->hasMany(CandidateProgress::class, 'step_id', 'id');
    }
}
