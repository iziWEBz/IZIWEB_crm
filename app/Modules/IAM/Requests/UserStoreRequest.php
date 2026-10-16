<?php

namespace App\Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,pm,specialist,candidate,client'],
            'profile_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Ім\'я" є обов\'язковим для заповнення.',
            'email.required' => 'Поле "Email" є обов\'язковим.',
            'email.email' => 'Введіть коректну адресу електронної пошти.',
            'email.unique' => 'Користувач з таким email вже зареєстрований у системі.',
            'password.required' => 'Пароль є обов\'язковим для заповнення.',
            'password.min' => 'Пароль має містити не менше 6 символів.',
            'role.required' => 'Обов\'язково вкажіть роль користувача.',
            'role.in' => 'Вказаної ролі не існує в системі.',
            'profile_data.array' => 'Мета-дані профілю мають бути передані у форматі масиву.',
        ];
    }
}
