<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Очищаємо кеш дозволів Spatie перед створенням
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Створюємо пермішени для базового захисту контурів
        Permission::create(['name' => 'access.admin', 'guard_name' => 'web']);
        Permission::create(['name' => 'access.pm', 'guard_name' => 'web']);
        Permission::create(['name' => 'access.specialist', 'guard_name' => 'web']);
        Permission::create(['name' => 'access.candidate', 'guard_name' => 'web']);
        Permission::create(['name' => 'access.client', 'guard_name' => 'web']);

        // 2. Створюємо ролі з нашої матриці доступу
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $pmRole = Role::create(['name' => 'pm', 'guard_name' => 'web']);
        $specialistRole = Role::create(['name' => 'specialist', 'guard_name' => 'web']);
        $candidateRole = Role::create(['name' => 'candidate', 'guard_name' => 'web']);
        $clientRole = Role::create(['name' => 'client', 'guard_name' => 'web']);

        // 3. Прив'язуємо базові дозволи до ролей (Адмін отримує все)
        $adminRole->givePermissionTo(Permission::all());
        $pmRole->givePermissionTo('access.pm');
        $specialistRole->givePermissionTo('access.specialist');
        $candidateRole->givePermissionTo('access.candidate');
        $clientRole->givePermissionTo('access.client');
    }

    public function down(): void
    {
        // Логіка відкату міграції
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        Schema::disableForeignKeyConstraints();
        Role::truncate();
        Permission::truncate();
        Schema::enableForeignKeyConstraints();
    }
};
