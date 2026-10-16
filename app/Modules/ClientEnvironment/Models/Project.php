<?php

namespace App\Modules\ClientEnvironment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    // Явно вказуємо назву таблиці в СУБД
    protected $table = 'projects';

    // Поля для масового заповнення
    protected $fillable = [
        'parent_id',
        'client_id',
        'brief_template_id',
        'title',
        'status', // briefing, ready_for_sign, in_development, completed
        'brief_data',
        'budget',
    ];

    // Автоматичне приведення типів
    protected $casts = [
        'brief_data' => 'array', // Автоматично розпаковує JSONB відповідей брифу в PHP-масив
        'budget' => 'integer',
        'parent_id' => 'integer',
        'client_id' => 'integer',
        'brief_template_id' => 'integer',
    ];

    /**
     * Рекурсивний зв'язок «Багато до одного» (Зворотний зв'язок до батьківського проєкту).
     * Повертає головний контракт, до якого належить цей етап.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /**
     * Рекурсивний зв'язок «Один до багатьох» (Дочірні етапи).
     * Повертає всі підпроєкти/етапи виробництва (Дизайн, Розробка, SEO), закріплені за цим контрактом.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з користувачем (Клієнтом-Власником проєкту).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з шаблоном брифу.
     */
    public function briefTemplate(): BelongsTo
    {
        return $this->belongsTo(BriefTemplate::class, 'brief_template_id', 'id');
    }

    /**
     * Зв'язок «Один до багатьох» з універсальними модерованими тікетами.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(CommunicationTicket::class, 'project_id', 'id');
    }

    /**
     * Зв'язок «Один до багатьох» з логом цифрових підписів документів.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class, 'project_id', 'id');
    }
}
