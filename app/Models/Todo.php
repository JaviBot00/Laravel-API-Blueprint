<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OpenApi\Attributes as OA;

/**
 * Modelo Todo.
 *
 * Representa una tarea en la API de ejemplo.
 * El trait AuditableTrait registra automáticamente en la tabla `audits`
 * cualquier creación, modificación o eliminación de una tarea, incluyendo
 * qué usuario lo hizo y qué valores tenía antes y después del cambio.
 *
 */

#[OA\Schema(
  schema: "Todo",
  title: "Todo Model",
  description: "Esquema del modelo de Tareas",
  properties: [
    new OA\Property(property: "id", type: "integer", example: 1),
    new OA\Property(property: "title", type: "string", example: "Estudiar Laravel"),
    new OA\Property(property: "completed", type: "boolean", example: false),
    new OA\Property(property: "user_id", type: "integer", example: 5),
    new OA\Property(property: "created_at", type: "string", format: "date-time"),
    new OA\Property(property: "updated_at", type: "string", format: "date-time")
  ]
)]
class Todo extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'title',
        'completed',
        'user_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    // =========================================================================
    // Configuración de Auditoría
    // =========================================================================

    /**
     * Campos que se incluirán en el registro de auditoría.
     * Si no se define, audita todos los campos de $fillable.
     */
    protected $auditInclude = [
        'title',
        'completed',
    ];

    // =========================================================================
    // Relaciones Eloquent
    // =========================================================================

    /**
     * Cada tarea pertenece a un único usuario.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
