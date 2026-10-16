<?php

namespace App\Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Визначає, чи має користувач право виконувати цей запит.
     */
    public function authorize(): bool
    {
        return true; // Безпека контролюється Middleware на рівні маршрутів
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
