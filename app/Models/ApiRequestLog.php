<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

/**
 * Modelo ApiRequestLog.
 *
 * Registra cada petición que llega a la API para generar estadísticas de uso.
 * Es rellenado por el middleware LogApiRequest en cada petición autenticada.
 *
 * Campos:
 *   - user_id:        ID del usuario que hizo la petición (null si no autenticado)
 *   - method:         Método HTTP (GET, POST, PUT, DELETE...)
 *   - path:           Ruta del endpoint (/api/todos, /api/users...)
 *   - status_code:    Código de respuesta HTTP (200, 201, 404, 422...)
 *   - response_time:  Tiempo de respuesta en milisegundos
 *   - ip_address:     IP del cliente
 */

#[OA\Schema(
  schema: "ApiRequestLog",
  title: "Registro de Petición API",
  description: "Esquema del modelo que registra cada petición a la API para estadísticas de uso",
  properties: [
    new OA\Property(property: "id", type: "integer", example: 1),
    new OA\Property(property: "user_id", type: "integer", nullable: true, example: 5, description: "ID del usuario que hizo la petición (null si no autenticado)"),
    new OA\Property(property: "method", type: "string", example: "GET", description: "Método HTTP"),
    new OA\Property(property: "path", type: "string", example: "/api/todos", description: "Ruta del endpoint"),
    new OA\Property(property: "status_code", type: "integer", example: 200, description: "Código de respuesta HTTP"),
    new OA\Property(property: "response_time", type: "integer", example: 150, description: "Tiempo de respuesta en milisegundos"),
    new OA\Property(property: "ip_address", type: "string", example: "127.0.0.1", description: "IP del cliente"),
    new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2024-06-01T12:34:56Z", description: "Fecha y hora de la petición")
  ]
)]
class ApiRequestLog extends Model
{
    // Esta tabla nunca se actualiza, solo se insertan registros
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'status_code',
        'response_time',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'response_time' => 'integer',
        'status_code'   => 'integer',
    ];

    // =========================================================================
    // Relaciones
    // =========================================================================

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // Scopes — filtros reutilizables para las queries de estadísticas
    // =========================================================================

    /**
     * Filtra por rango de fechas.
     * Uso: ApiRequestLog::inDateRange($from, $to)->get()
     */
    public function scopeInDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Filtra por usuario específico.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
