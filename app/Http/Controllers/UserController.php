<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

/**
 * Controlador de gestión de usuarios.
 * Solo accesible por administradores (ver routes/api.php).
 *
 * @OA\Tag(name="Users", description="Gestión de usuarios (solo admin)")
 */

#[OA\Tag(name: "Users", description: "Gestión de usuarios (solo admin)")]
class UserController extends Controller
{
    // =========================================================================
    // GET /api/users
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/users",
     *   tags={"Users"},
     *   summary="Listar todos los usuarios",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Lista de usuarios con sus roles")
     * )
     */

    #[OA\Get(
        path: "/api/users",
        tags: ["Users"],
        summary: "Listar todos los usuarios",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Lista de usuarios con sus roles")
        ]
    )]
    public function index(): JsonResponse
    {
        // with('roles') carga los roles en la misma query (evita N+1 queries)
        $users = User::with('roles')->get();

        return response()->json($users);
    }

    // =========================================================================
    // POST /api/users
    // =========================================================================

    /**
     * @OA\Post(
     *   path="/api/users",
     *   tags={"Users"},
     *   summary="Crear un nuevo usuario y asignarle un rol",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","email","password","role"},
     *       @OA\Property(property="name",     type="string", example="John Doe"),
     *       @OA\Property(property="email",    type="string", example="john@example.com"),
     *       @OA\Property(property="password", type="string", example="secret123"),
     *       @OA\Property(property="role",     type="string", example="user", enum={"admin","user"})
     *     )
     *   ),
     *   @OA\Response(response=201, description="Usuario creado correctamente")
     * )
     */

    #[OA\Post(
        path: "/api/users",
        tags: ["Users"],
        summary: "Crear un nuevo usuario y asignarle un rol",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "role"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "secret123"),
                    new OA\Property(property: "role", type: "string", example: "user", enum: ["admin", "user"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Usuario creado correctamente")
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        // assignRole es un método de Spatie\Permission\Traits\HasRoles
        // Busca el rol en la tabla 'roles' y crea el registro en 'model_has_roles'
        $user->assignRole($request->input('role', 'user'));

        return response()->json($user->load('roles'), 201);
    }

    // =========================================================================
    // GET /api/users/{id}
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/users/{id}",
     *   tags={"Users"},
     *   summary="Obtener un usuario por ID",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Datos del usuario con su rol"),
     *   @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */

    #[OA\Get(
        path: "/api/users/{id}",
        tags: ["Users"],
        summary: "Obtener un usuario por ID",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Datos del usuario con su rol"),
            new OA\Response(response: 404, description: "Usuario no encontrado")
        ]
    )]
    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('roles'));
    }

    // =========================================================================
    // PUT /api/users/{id}/role
    // =========================================================================

    /**
     * @OA\Put(
     *   path="/api/users/{id}/role",
     *   tags={"Users"},
     *   summary="Cambiar el rol de un usuario",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"role"},
     *       @OA\Property(property="role", type="string", example="admin", enum={"admin","user"})
     *     )
     *   ),
     *   @OA\Response(response=200, description="Rol actualizado correctamente")
     * )
     */

    #[OA\Put(
        path: "/api/users/{id}/role",
        tags: ["Users"],
        summary: "Cambiar el rol de un usuario",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["role"],
                properties: [
                    new OA\Property(property: "role", type: "string", example: "admin", enum: ["admin", "user"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Rol actualizado correctamente")
        ]
    )]
    public function updateRole(User $user): JsonResponse
    {
        $role = request()->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ])['role'];

        // syncRoles elimina los roles actuales y asigna el nuevo.
        // Útil cuando cada usuario solo puede tener un rol.
        $user->syncRoles([$role]);

        return response()->json($user->load('roles'));
    }

    // =========================================================================
    // DELETE /api/users/{id}
    // =========================================================================

    /**
     * @OA\Delete(
     *   path="/api/users/{id}",
     *   tags={"Users"},
     *   summary="Eliminar un usuario",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Usuario eliminado correctamente")
     * )
     */

    #[OA\Delete(
        path: "/api/users/{id}",
        tags: ["Users"],
        summary: "Eliminar un usuario",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Usuario eliminado correctamente")
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }
}
