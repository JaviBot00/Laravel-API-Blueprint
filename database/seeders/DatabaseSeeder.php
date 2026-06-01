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
 *   - Roles para la API REST (guard 'api'):  admin, user
 *   - Rol para el panel Filament (guard 'web'): super_admin
 *   - Un usuario administrador con ambos roles
 *   - Un usuario normal con algunas tareas de ejemplo
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 1. Roles para la API REST — guard 'api'
        //
        // Estos roles protegen los endpoints JWT en routes/api.php mediante
        // el middleware 'role:admin' de Spatie.
        // =====================================================================

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $userRole  = Role::firstOrCreate(['name' => 'user',  'guard_name' => 'api']);

        // =====================================================================
        // 2. Rol para el panel Filament — guard 'web'
        //
        // Shield usa el guard 'web' para el panel /admin. Este rol es
        // independiente de los anteriores: un usuario puede tener 'admin'
        // (para la API) y 'super_admin' (para el panel) simultáneamente.
        //
        // IMPORTANTE: este bloque debe ejecutarse ANTES de shield:install,
        // o shield:install lo creará solo. Si ejecutas shield:install --fresh
        // después del seeder, los permisos se regeneran pero el rol persiste.
        // =====================================================================

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // =====================================================================
        // 3. Usuario administrador
        // =====================================================================

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole($adminRole);      // acceso a rutas /api/admin/*
        $admin->assignRole($superAdminRole); // acceso al panel /admin

        // =====================================================================
        // 4. Usuario normal con tareas de ejemplo
        // =====================================================================

        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'Usuario de prueba',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($userRole);

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
        $this->command->info('   API   → admin@example.com / password  (rol: admin,      guard: api)');
        $this->command->info('   Panel → admin@example.com / password  (rol: super_admin, guard: web)');
        $this->command->info('   User  → user@example.com  / password  (rol: user,        guard: api)');
        $this->command->info('   Panel: http://localhost:8080/admin');
    }
}
