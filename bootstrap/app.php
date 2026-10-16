<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('app/Modules/IAM/Routes/api.php'));

            Route::prefix('api/v1')
                ->middleware(['api', 'auth:sanctum']) 
                ->group(base_path('app/Modules/Onboarding/Routes/api.php'));

            Route::prefix('api/v1')
                ->middleware(['api', 'auth:sanctum'])
                ->group(base_path('app/Modules/ClientEnvironment/Routes/api.php'));
            Route::prefix('api/v1')
            ->middleware(['api', 'auth:sanctum'])
            ->group(base_path('app/Modules/Management/Routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Реєструємо аліас для нашого кастомного Middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class, // Тепер ми можемо писати middleware('role:admin')
        ]);
        /*
         * ПЕРЕХОПЛЕННЯ РЕДІРЕКТУ ДЛЯ НЕАВТЕНТИФІКОВАНИХ КОРИСТУВАЧІВ
         */
        $middleware->redirectGuestsTo(fn () => null); 
        // Отключаем CSRF для всех наших модульных API
        $middleware->validateCsrfTokens(except: [
            'api/v1/*' 
        ]);

        // Глобально включаем CORS обработчик для всех AJAX запросов (включая Swagger)
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        /*
         * Використовуємо повний шлях Illuminate\Http\Request, 
         * щоб уникнути конфліктів аліасів у ядрі PHP.
         */
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        // Повертаємо стандартизований JSON українською мовою для неавторизованих запитів
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Помилка автентифікації. Будь ласка, авторизуйтесь у системі (надайте валідний токен).'
            ], 401);
        });
    })->create();
