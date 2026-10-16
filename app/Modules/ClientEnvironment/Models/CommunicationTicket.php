<?php

namespace App\Modules\ClientEnvironment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationTicket extends Model
{
    // Явно вказуємо назву таблиці в СУБД
    protected $table = 'communication_tickets';

    // Поля для масового заповнення
    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'category', // tech_issue, documents, edits
        'status', // open, in_progress, waiting_client, resolved
        'message_from_specialist',
        'message_to_client',
    ];

    // Приведення типів даних при читанні з бази
    protected $casts = [
        'project_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Зворотний зв'язок «Багато до одного» з проєктом.
     * Посилається на головний контракт або дочірній виробничий спринт.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Зворотний зв'язок «Багато до одного» з автором тікета (користувачем системи).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
