<?php

namespace App\Modules\IAM\Controllers;

use App\Modules\IAM\Controllers\BaseController;
use App\Modules\IAM\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends BaseController
{
    #[OA\Post(
        path: "/auth/login",
        summary: "Авторизація користувача (Вхід)",
        tags: ["Автентифікація"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "admin@crm.local"),
                new OA\Property(property: "password", type: "string", format: "password", example: "admin123")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Успішна авторизація",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "access_token", type: "string", example: "1|laravel_sanctum_token..."),
                new OA\Property(property: "token_type", type: "string", example: "Bearer")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Помилка валідації")]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Невірний логін або пароль.'],
            ]);
        }

        $token = $user->createToken('crm_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Авторизація успішна.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    #[OA\Post(
        path: "/auth/logout",
        summary: "Завершення сесії (Вихід)",
        tags: ["Автентифікація"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Токен успішно анульовано",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success")
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Неавторизований запит")]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Сесія успішно завершена. Токен анульовано.'
        ], 200);
    }

    /**
     * Отримання профілю поточного авторизованого користувача
     */
    #[OA\Get(
        path: "/auth/me",
        summary: "Отримання профілю поточного користувача",
        tags: ["Автентифікація"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Дані профілю успішно отримано разом із ролями",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "user",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Олександр Адмін"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "admin@crm.local"),
                        new OA\Property(
                            property: "roles",
                            type: "array",
                            items: new OA\Items(type: "string", example: "admin")
                        ),
                        new OA\Property(
                            property: "profile_data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "phone", type: "string", example: "+380671112233"),
                                new OA\Property(property: "position", type: "string", example: "CEO / Founder")
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Неавторизований запит (відсутній або невалідний токен)")]
    
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Динамічно підвантажуємо зв'язок із ролями Spatie, щоб вони закешувалися в об'єкті
        $user->roles_list = $user->getRoleNames(); 

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles_list, // Масив рядків (наприклад: ["admin"] або ["pm"])
                'profile_data' => $user->profile_data,
                'created_at' => $user->created_at,
            ]
        ], 200);
    }


}
