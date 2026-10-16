<?php

namespace App\Modules\Onboarding\Models;

use App\Modules\IAM\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProgress extends Model
{
    // Явно вказуємо назву таблиці в PostgreSQL
    protected $table = 'candidate_progress';

    // Поля для масового заповнення
    protected $fillable = [
        'user_id',
        'step_id',
        'status', // Поточний стан: in_progress, passed, review
        'test_answers',
        'task_url',
    ];

    // Приведення типів даних
    protected $casts = [
        'test_answers' => 'array', // Автоматично конвертує лог відповідей у PHP-масив
    ];

    /**
     * Зворотний зв'язок «Багато до одного» з моделлю користувача (модуль IAM).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з конкретним кроком навчання.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseStep::class, 'step_id', 'id');
    }
}
