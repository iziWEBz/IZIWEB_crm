<?php

namespace App\Modules\Management\Models;

use App\Models\User;
use App\Modules\ClientEnvironment\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalTask extends Model
{
    // Явно вказуємо назву таблиці в PostgreSQL
    protected $table = 'global_tasks';

    // Поля для масового заповнення
    protected $fillable = [
        'project_id',
        'pm_id',
        'title',
        'description',
        'status', // pending, in_progress, review, completed
        'result_url',
        'deadline_at',
    ];

    // Автоматичне приведення типів при отриманні з бази
    protected $casts = [
        'deadline_at' => 'datetime', // Конвертує таймстамп у зручний об'єкт Carbon
        'project_id' => 'integer',
        'pm_id' => 'integer',
    ];

    /**
     * Зворотний зв'язок «Багато до одного» з проєктом (або його конкретним виробничим етапом).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з відповідальним менеджером (роль PM).
     */
    public function pm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pm_id', 'id');
    }
}
