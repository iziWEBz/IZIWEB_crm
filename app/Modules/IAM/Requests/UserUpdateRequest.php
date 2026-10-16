<?php

namespace App\Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,pm,specialist,candidate,client'],
            'profile_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Ім\'я" не може бути порожнім.',
            'email.required' => 'Поле "Email" є обов\'язковим.',
            'email.email' => 'Введіть коректну електронну пошту.',
            'email.unique' => 'Цей email вже зайнятий іншим користувачем системи.',
            'password.min' => 'Новий пароль має містити не менше 6 символів.',
            'role.required' => 'Вкажіть роль користувача.',
            'role.in' => 'Вказаної ролі не існує в системі.',
            'profile_data.array' => 'Некоректний формат мета-даних профілю.',
        ];
    }
}
