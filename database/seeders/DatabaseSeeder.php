<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * DatabaseSeeder — datos iniciales para el proyecto.
 *
 * Se ejecuta con: php artisan migrate --seed
 * O solo el seeder: php artisan db:seed
 *
 * Crea:
 *   - Los roles 'admin' y 'user'
 *   - Un usuario administrador
 *   - Un usuario normal con algunas tareas de ejemplo
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. Crear roles con Spatie Permission
        // =====================================================================

        // firstOrCreate evita errores si se ejecuta el seeder más de una vez
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $userRole  = Role::firstOrCreate(['name' => 'user',  'guard_name' => 'api']);

        // =====================================================================
        // 2. Crear usuario administrador
        // =====================================================================

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        // assignRole es un método de Spatie\Permission\Traits\HasRoles
        $admin->assignRole($adminRole);

        // =====================================================================
        // 3. Crear usuario normal con tareas de ejemplo
        // =====================================================================

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'Usuario de prueba',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($userRole);

        // Crear algunas tareas de ejemplo para el usuario normal
        $todos = [
            ['title' => 'Leer la documentación de Laravel', 'completed' => true],
            ['title' => 'Configurar el entorno Docker',     'completed' => true],
            ['title' => 'Implementar autenticación JWT',    'completed' => false],
            ['title' => 'Escribir tests de la API',         'completed' => false],
        ];

        foreach ($todos as $todo) {
            Todo::firstOrCreate(
                ['title' => $todo['title'], 'user_id' => $user->id],
                ['completed' => $todo['completed']]
            );
        }

        $this->command->info('✅ Seeder ejecutado correctamente.');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   User:  user@example.com  / password');
    }
}
