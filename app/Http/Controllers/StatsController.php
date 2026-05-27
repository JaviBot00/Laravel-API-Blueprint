<?php

namespace App\Http\Controllers;

use App\Models\ApiRequestLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

/**
 * Controlador de estadísticas y auditoría.
 * Solo accesible por administradores (ver routes/api.php).
 *
 * @OA\Tag(name="Stats", description="Estadísticas de uso y registros de auditoría (solo admin)")
 */
class StatsController extends Controller
{
    // =========================================================================
    // GET /api/stats/usage
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/stats/usage",
     *   tags={"Stats"},
     *   summary="Estadísticas globales de uso de la API",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Resumen de peticiones por endpoint, usuario y código HTTP")
     * )
     */
    public function usage(): JsonResponse
    {
        return response()->json([

            // Total de peticiones agrupadas por endpoint y método HTTP
            'by_endpoint' => ApiRequestLog::select('method', 'path', DB::raw('count(*) as total'))
                ->groupBy('method', 'path')
                ->orderByDesc('total')
                ->get(),

            // Total de peticiones agrupadas por usuario
            'by_user' => ApiRequestLog::select('user_id', DB::raw('count(*) as total'))
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->get(),

            // Distribución de códigos de respuesta HTTP (200, 201, 404, 422...)
            'by_status' => ApiRequestLog::select('status_code', DB::raw('count(*) as total'))
                ->groupBy('status_code')
                ->orderBy('status_code')
                ->get(),

            // Tiempo medio de respuesta por endpoint
            'avg_response_time' => ApiRequestLog::select('path', DB::raw('avg(response_time) as avg_ms'))
                ->groupBy('path')
                ->orderByDesc('avg_ms')
                ->get(),
        ]);
    }

    // =========================================================================
    // GET /api/stats/usage/user/{id}
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/stats/usage/user/{id}",
     *   tags={"Stats"},
     *   summary="Estadísticas de uso de un usuario específico",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Peticiones del usuario agrupadas por endpoint")
     * )
     */
    public function usageByUser(User $user): JsonResponse
    {
        return response()->json([
            'user'        => $user->only('id', 'name', 'email'),
            'total_calls' => ApiRequestLog::forUser($user->id)->count(),
            'by_endpoint' => ApiRequestLog::forUser($user->id)
                ->select('method', 'path', DB::raw('count(*) as total'))
                ->groupBy('method', 'path')
                ->orderByDesc('total')
                ->get(),
            'last_10'     => ApiRequestLog::forUser($user->id)
                ->latest('created_at')
                ->limit(10)
                ->get(),
        ]);
    }

    // =========================================================================
    // GET /api/stats/audits
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/stats/audits",
     *   tags={"Stats"},
     *   summary="Registro completo de auditoría (quién cambió qué y cuándo)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Lista de eventos de auditoría con antes/después")
     * )
     */
    public function audits(Request $request): JsonResponse
    {
        // La tabla `audits` la gestiona owen-it/laravel-auditing automáticamente.
        // Contiene: user_id, event (created/updated/deleted), old_values, new_values...
        $audits = Audit::with('user:id,name,email')
            ->when($request->query('user_id'), fn($q, $userId) => $q->where('user_id', $userId))
            ->when($request->query('model'),   fn($q, $model)  => $q->where('auditable_type', 'like', "%{$model}%"))
            ->latest()
            ->paginate(50);

        return response()->json($audits);
    }
}
