<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Modelo User.
 *
 * Implementa tres interfaces/traits clave:
 *   - JWTSubject:  permite que este modelo sea el "sujeto" del token JWT.
 *   - HasRoles:    añade los métodos de Spatie para asignar y comprobar roles/permisos.
 *   - Auditable:   registra automáticamente cada cambio (create/update/delete) en audit_logs.
 *
 * @OA\Schema(
 *   schema="User",
 *   @OA\Property(property="id",    type="integer", example=1),
 *   @OA\Property(property="name",  type="string",  example="Ada Lovelace"),
 *   @OA\Property(property="email", type="string",  example="ada@example.com"),
 * )
 */
class User extends Authenticatable implements JWTSubject, Auditable
{
    use HasFactory, Notifiable, HasRoles, AuditableTrait;

    // -------------------------------------------------------------------------
    // Campos permitidos para asignación masiva ($model->fill([...]))
    // -------------------------------------------------------------------------
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // -------------------------------------------------------------------------
    // Campos que NUNCA se incluirán en la respuesta JSON ni en arrays
    // -------------------------------------------------------------------------
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // -------------------------------------------------------------------------
    // Casting de tipos: convierte automáticamente el campo al tipo indicado
    // -------------------------------------------------------------------------
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',   // hashea automáticamente al asignar
    ];

    // =========================================================================
    // Métodos requeridos por JWTSubject
    // =========================================================================

    /**
     * Devuelve el identificador único que se guardará dentro del token JWT.
     * Por defecto es el ID del usuario en la base de datos.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Permite añadir claims (campos extra) al payload del token JWT.
     * Aquí añadimos el email y el rol principal para poder leerlos sin
     * hacer una consulta a la base de datos.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'role'  => $this->getRoleNames()->first(),
        ];
    }

    // =========================================================================
    // Relaciones Eloquent
    // =========================================================================

    /**
     * Un usuario puede tener muchas tareas (TODOs).
     */
    public function todos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Todo::class);
    }
}
