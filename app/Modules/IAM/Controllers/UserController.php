<?php

namespace App\Modules\IAM\Controllers;

use App\Modules\IAM\Controllers\BaseController;
use App\Modules\IAM\Requests\UserStoreRequest;
use App\Modules\IAM\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

class UserController extends BaseController
{
    #[OA\Get(
        path: "/iam/users",
        summary: "Список усіх користувачів системи (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "role",
        in: "query",
        description: "Фільтр за назвою ролі (admin, pm, specialist, candidate, client)",
        required: false,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "Список користувачів успішно отримано",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "users", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->role($request->input('role'));
        }

        $users = $query->get()->map(function ($user) {
            $user->roles_list = $user->getRoleNames();
            return $user;
        });

        return response()->json([
            'status' => 'success',
            'users' => $users
        ], 200);
    }

    #[OA\Get(
        path: "/iam/roles",
        summary: "Список усіх ролей системи (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Список ролей успішно отримано",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    public function rolesList(): JsonResponse
    {
        $roles = Role::all()->map(function($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->getPermissionNames()
            ];
        });

        return response()->json([
            'status' => 'success',
            'roles' => $roles
        ], 200);
    }

    #[OA\Get(
        path: "/iam/users/{id}",
        summary: "Деталі користувача за ID (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))]
    #[OA\Response(response: 200, description: "Дані користувача успішно отримано")]
    #[OA\Response(response: 404, description: "Користувача не знайдено")]
    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->roles_list = $user->getRoleNames();

        return response()->json([
            'status' => 'success',
            'user' => $user
        ], 200);
    }

    #[OA\Post(
        path: "/iam/users/store",
        summary: "Ручне створення користувача (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
                content: new OA\JsonContent(
            required: ["name", "email", "role"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Тест Розробник"),
                new OA\Property(property: "email", type: "string", format: "email", example: "dev_modified@crm.local"),
                new OA\Property(property: "role", type: "string", example: "specialist"),
                new OA\Property(property: "password", type: "string", format: "password", description: "Передавати тільки якщо потрібно змінити пароль", example: "new_password123"),
                new OA\Property(
                    property: "profile_data",
                    type: "object",
                    description: "Мета-дані профілю (JSONB)",
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "+380679998877"),
                        new OA\Property(property: "specialization", type: "string", example: "backend")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 201, description: "Користувача успішно створено")]
    public function store(UserStoreRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_data' => $request->input('profile_data', []),
        ]);

        $user->assignRole($request->role);
        $user->roles_list = $user->getRoleNames();

        return response()->json([
            'status' => 'success',
            'message' => 'Користувача успішно створено в системі.',
            'user' => $user
        ], 201);
    }

    #[OA\Put(
        path: "/iam/users/{id}/update",
        summary: "Редагування користувача (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "ID користувача для оновлення",
        required: true,
        schema: new OA\Schema(type: "integer", example: 4)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Дані для оновлення профілю користувача",
        content: new OA\JsonContent(
            required: ["name", "email", "role"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "Тест Розробник"),
                new OA\Property(property: "email", type: "string", format: "email", example: "dev_modified@crm.local"),
                new OA\Property(property: "role", type: "string", example: "specialist"),
                new OA\Property(property: "password", type: "string", format: "password", description: "Передавати тільки якщо потрібно змінити пароль", example: "new_password123"),
                new OA\Property(
                    property: "profile_data",
                    type: "object",
                    description: "Мета-дані профілю (JSONB)",
                    properties: [
                        new OA\Property(property: "phone", type: "string", example: "+380679998877"),
                        new OA\Property(property: "specialization", type: "string", example: "backend")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Дані користувача успішно оновлено",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "Дані користувача успешно оновлено.")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Помилка валідації даних")]
    #[OA\Response(response: 404, description: "Користувача не знайдено")]
    public function update(UserUpdateRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('profile_data')) {
            $user->profile_data = $request->input('profile_data');
        }

        $user->save();

        $user->syncRoles([$request->role]);
        $user->roles_list = $user->getRoleNames();

        return response()->json([
            'status' => 'success',
            'message' => 'Дані користувача успішно оновлено.',
            'user' => $user
        ], 200);
    }

    #[OA\Delete(
        path: "/iam/users/{id}",
        summary: "Видалення користувача із системи (Тільки для Admin)",
        tags: ["Управління користувачами"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "ID користувача, якого потрібно видалити",
        required: true,
        schema: new OA\Schema(type: "integer", example: 5)
    )]
    #[OA\Response(
        response: 200,
        description: "Користувача успішно видалено",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "Користувача успішно видалено із системи.")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Користувача не знайдено в базі даних")]
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Користувача успішно видалено із системи.'
        ], 200);
    }

}
