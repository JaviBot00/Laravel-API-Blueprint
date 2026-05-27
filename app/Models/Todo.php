<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Modelo Todo.
 *
 * Representa una tarea en la API de ejemplo.
 * El trait AuditableTrait registra automáticamente en la tabla `audits`
 * cualquier creación, modificación o eliminación de una tarea, incluyendo
 * qué usuario lo hizo y qué valores tenía antes y después del cambio.
 *
 * @OA\Schema(
 *   schema="Todo",
 *   @OA\Property(property="id",        type="integer", example=1),
 *   @OA\Property(property="title",     type="string",  example="Comprar leche"),
 *   @OA\Property(property="completed", type="boolean", example=false),
 *   @OA\Property(property="user_id",   type="integer", example=1),
 * )
 */
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
