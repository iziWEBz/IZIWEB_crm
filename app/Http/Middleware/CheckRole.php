<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Обробка вхідного запиту.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Масив дозволених ролей, переданих через кому у файлі маршрутів
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Перевіряємо, чи користувач взагалі авторизований
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Помилка автентифікації. Доступ заблоковано.'
            ], 401);
        }

        // 2. Керівництво (admin) має безлімітний повний доступ до всіх контурів системи за замовчуванням
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // 3. Перевіряємо за допомогою Spatie трейту, чи є у користувача хоча б одна з потрібних ролей
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // 4. Якщо жодна роль не збіглася — повертаємо строгий статус 403 Forbidden
        return response()->json([
            'status' => 'error',
            'message' => 'Доступ заборонено. У вас немає прав для перегляду цього контуру системи.'
        ], 403);
    }
}
