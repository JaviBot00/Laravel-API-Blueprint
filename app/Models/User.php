<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use OpenApi\Attributes as OA;

/**
 * Modelo User.
 *
 * Implementa cuatro interfaces/traits:
 *   - JWTSubject:    sujeto del token JWT para la API REST.
 *   - HasRoles:      roles y permisos de Spatie (guard 'api' para la API).
 *   - Auditable:     registra cada cambio en audit_logs.
 *   - FilamentUser:  permite o deniega el acceso al panel /admin.
 */

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id',    type: 'integer', example: 1),
        new OA\Property(property: 'name',  type: 'string',  example: 'Ada Lovelace'),
        new OA\Property(property: 'email', type: 'string',  example: 'ada@example.com'),
    ]
)]
class User extends Authenticatable implements JWTSubject, Auditable, FilamentUser
{
    use HasFactory, Notifiable, HasRoles, AuditableTrait, HasPanelShield;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // =========================================================================
    // JWTSubject — sin cambios
    // =========================================================================

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'role'  => $this->getRoleNames()->first(),
        ];
    }

    // =========================================================================
    // FilamentUser
    //
    // HasPanelShield (incluido arriba) ya implementa canAccessPanel() y da
    // acceso a usuarios con rol 'super_admin' (guard 'web').
    //
    // Lo sobreescribimos para incluir también el rol 'admin' existente,
    // de modo que el administrador de la API pueda entrar al panel sin
    // necesidad de asignarle un segundo rol manualmente.
    //
    // Nota: hasAnyRole() comprueba ambos guards (api y web) automáticamente.
    // =========================================================================

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin']);
    }

    // =========================================================================
    // Relaciones
    // =========================================================================

    public function todos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Todo::class);
    }
}
