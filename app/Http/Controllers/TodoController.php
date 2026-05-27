<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para el CRUD de tareas (TODOs).
 *
 * Cada método tiene UNA responsabilidad:
 *   index   → listar
 *   store   → crear
 *   show    → mostrar uno
 *   update  → actualizar
 *   destroy → eliminar
 *
 * La validación de datos NO está aquí: vive en StoreTodoRequest / UpdateTodoRequest.
 * La autorización NO está aquí: vive en TodoPolicy.
 *
 * @OA\Tag(name="Todos", description="Gestión de tareas")
 */
class TodoController extends Controller
{
    public function __construct()
    {
        // Aplica la autorización de TodoPolicy a todos los métodos de este controlador.
        // Laravel buscará automáticamente App\Policies\TodoPolicy.
        $this->authorizeResource(Todo::class, 'todo');
    }

    // =========================================================================
    // GET /api/todos
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/todos",
     *   tags={"Todos"},
     *   summary="Listar todas las tareas del usuario autenticado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Lista de tareas",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Todo"))
     *   )
     * )
     */
    public function index(): JsonResponse
    {
        // Los admins ven todas las tareas. Los usuarios solo las suyas.
        // Esta lógica podría vivir también en la Policy si se prefiere.
        $todos = Auth::user()->hasRole('admin')
            ? Todo::with('user')->get()
            : Auth::user()->todos;

        return response()->json($todos);
    }

    // =========================================================================
    // POST /api/todos
    // =========================================================================

    /**
     * @OA\Post(
     *   path="/api/todos",
     *   tags={"Todos"},
     *   summary="Crear una nueva tarea",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreTodoRequest")
     *   ),
     *   @OA\Response(response=201, description="Tarea creada",
     *     @OA\JsonContent(ref="#/components/schemas/Todo")
     *   )
     * )
     */
    public function store(StoreTodoRequest $request): JsonResponse
    {
        // $request->validated() devuelve SOLO los campos que pasaron la validación.
        // Nunca usar $request->all() para crear modelos: riesgo de mass assignment.
        $todo = Todo::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        // 201 Created es el código correcto para recursos recién creados
        return response()->json($todo, 201);
    }

    // =========================================================================
    // GET /api/todos/{id}
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/todos/{id}",
     *   tags={"Todos"},
     *   summary="Obtener una tarea por ID",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tarea encontrada",
     *     @OA\JsonContent(ref="#/components/schemas/Todo")
     *   ),
     *   @OA\Response(response=404, description="Tarea no encontrada")
     * )
     */
    public function show(Todo $todo): JsonResponse
    {
        // Laravel resuelve automáticamente el modelo por ID gracias a
        // "Route Model Binding". Si no existe, devuelve 404 automáticamente.
        return response()->json($todo);
    }

    // =========================================================================
    // PUT /api/todos/{id}
    // =========================================================================

    /**
     * @OA\Put(
     *   path="/api/todos/{id}",
     *   tags={"Todos"},
     *   summary="Actualizar una tarea",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/UpdateTodoRequest")
     *   ),
     *   @OA\Response(response=200, description="Tarea actualizada",
     *     @OA\JsonContent(ref="#/components/schemas/Todo")
     *   )
     * )
     */
    public function update(UpdateTodoRequest $request, Todo $todo): JsonResponse
    {
        $todo->update($request->validated());

        return response()->json($todo);
    }

    // =========================================================================
    // DELETE /api/todos/{id}
    // =========================================================================

    /**
     * @OA\Delete(
     *   path="/api/todos/{id}",
     *   tags={"Todos"},
     *   summary="Eliminar una tarea",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Tarea eliminada correctamente"),
     *   @OA\Response(response=403, description="Sin permisos para eliminar esta tarea")
     * )
     */
    public function destroy(Todo $todo): JsonResponse
    {
        $todo->delete();

        // 204 No Content: acción correcta, sin cuerpo de respuesta
        return response()->json(null, 204);
    }
}
