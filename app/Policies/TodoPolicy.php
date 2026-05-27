<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;

/**
 * Policy de autorización para el modelo Todo.
 *
 * Una Policy es una clase PHP con métodos que devuelven true/false
 * para responder a la pregunta "¿puede este usuario hacer esta acción?".
 *
 * Laravel llama a estos métodos automáticamente cuando el controlador
 * usa $this->authorizeResource() o $this->authorize().
 *
 * Métodos estándar que espera Laravel:
 *   viewAny  → ¿puede listar todos?
 *   view     → ¿puede ver uno?
 *   create   → ¿puede crear?
 *   update   → ¿puede actualizar?
 *   delete   → ¿puede eliminar?
 */
class TodoPolicy
{
    /**
     * Los administradores tienen acceso a todo sin pasar por las comprobaciones.
     * Este método "before" se ejecuta ANTES que cualquier otro método de la Policy.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Si el usuario es admin, devolvemos true directamente.
        // null significa "no opino, continúa con el método correspondiente".
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Cualquier usuario autenticado puede ver la lista de sus tareas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Un usuario solo puede ver sus propias tareas.
     */
    public function view(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }

    /**
     * Cualquier usuario autenticado puede crear tareas.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Un usuario solo puede actualizar sus propias tareas.
     */
    public function update(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }

    /**
     * Un usuario solo puede eliminar sus propias tareas.
     */
    public function delete(User $user, Todo $todo): bool
    {
        return $user->id === $todo->user_id;
    }
}
