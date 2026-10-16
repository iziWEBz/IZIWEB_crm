<?php

namespace App\Modules\ClientEnvironment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    // Явно вказуємо назву таблиці в PostgreSQL
    protected $table = 'contract_signatures';

    // Поля для масового заповнення
    protected $fillable = [
        'project_id',
        'user_id',
        'document_hash',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    // Автоматичне приведення типів даних
    protected $casts = [
        'signed_at' => 'datetime',
        'project_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Зворотний зв'язок «Багато до одного» з проєктом або конкретним етапом.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з користувачем (Клієнтом, який підписав договір).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
