<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
