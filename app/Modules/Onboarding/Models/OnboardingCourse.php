<?php

namespace App\Modules\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingCourse extends Model
{
    // Явно вказуємо назву таблиці в БД
    protected $table = 'onboarding_courses';

    // Дозволені поля для масового заповнення
    protected $fillable = [
        'title',
        'description',
        'specialization',
        'sort_order',
        'is_active',
    ];

    // Приведення типів даних при отриманні з БД
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Зв'язок «Один до багатьох» із кроками/завданнями курсу.
     * Автоматично сортує кроки за їх порядковим номером.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(KnowledgeBaseStep::class, 'course_id', 'id')
            ->orderBy('sort_order', 'asc');
    }
}
