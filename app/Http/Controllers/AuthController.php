<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

/**
 * @OA\Tag(name="Auth", description="Autenticación JWT")
 */

#[OA\Tag(name: "Auth", description: "Autenticación JWT")]
#[OA\Schema(
  schema: "TokenResponse",
  title: "Respuesta de Autenticación",
  description: "Estructura del token JWT devuelto tras un login o refresh correcto",
  properties: [
    new OA\Property(property: "access_token", type: "string", description: "Token de acceso JWT"),
    new OA\Property(property: "token_type", type: "string", example: "bearer"),
    new OA\Property(property: "expires_in", type: "integer", description: "Tiempo de vida en segundos", example: 3600)
  ]
)]
class AuthController extends Controller
{
    // =========================================================================
    // Login
    // =========================================================================

    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   tags={"Auth"},
     *   summary="Iniciar sesión y obtener token JWT",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email",    type="string", example="admin@example.com"),
     *       @OA\Property(property="password", type="string", example="password")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Login correcto, devuelve token JWT"),
     *   @OA\Response(response=401, description="Credenciales incorrectas")
     * )
     */
    #[OA\Post(
        path: "/api/auth/login",
        tags: ["Auth"],
        summary: "Iniciar sesión y obtener token JWT",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login correcto, devuelve token JWT"),
            new OA\Response(response: 401, description: "Credenciales incorrectas")
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        // Validamos que vengan email y password en el body
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::guard('api') usa el guard configurado con JWT en config/auth.php
        // Si las credenciales son correctas, devuelve el token. Si no, false.
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    // =========================================================================
    // Logout
    // =========================================================================

    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   tags={"Auth"},
     *   summary="Cerrar sesión e invalidar el token JWT",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Sesión cerrada correctamente")
     * )
     */

    #[OA\Post(
        path: "/api/auth/logout",
        tags: ["Auth"],
        summary: "Cerrar sesión e invalidar el token JWT",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Sesión cerrada correctamente")
        ]
    )]
    public function logout(): JsonResponse
    {
        // Invalida el token actual en la blacklist de JWT
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    // =========================================================================
    // Refresh
    // =========================================================================

    /**
     * @OA\Post(
     *   path="/api/auth/refresh",
     *   tags={"Auth"},
     *   summary="Renovar el token JWT antes de que expire",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Token renovado correctamente")
     * )
     */

    #[OA\Post(
        path: "/api/auth/refresh",
        tags: ["Auth"],
        summary: "Renovar el token JWT antes de que expire",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Token renovado correctamente")
        ]
    )]
    public function refresh(): JsonResponse
    {
        // Genera un nuevo token invalidando el anterior automáticamente
        $token = Auth::guard('api')->refresh();

        return $this->respondWithToken($token);
    }

    // =========================================================================
    // Me — datos del usuario autenticado
    // =========================================================================

    /**
     * @OA\Get(
     *   path="/api/auth/me",
     *   tags={"Auth"},
     *   summary="Obtener datos del usuario autenticado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Datos del usuario actual")
     * )
     */

    #[OA\Get(
        path: "/api/auth/me",
        tags: ["Auth"],
        summary: "Obtener datos del usuario autenticado",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Datos del usuario actual")
        ]
    )]
    public function me(): JsonResponse
    {
        return response()->json(Auth::guard('api')->user());
    }

    // =========================================================================
    // Helper privado — formato de respuesta del token
    // =========================================================================

    /**
     * Devuelve siempre la misma estructura cuando entregamos un token.
     * Centralizarlo aquí evita repetir el array en login() y refresh().
     */
    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            // ttl() devuelve los minutos de vida del token (configurado en .env)
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}

// /**
//  * @OA\Schema(
//  * schema="TokenResponse",
//  * title="Respuesta de Autenticación",
//  * description="Estructura del token JWT devuelto tras un login o refresh correcto",
//  * @OA\Property(property="access_token", type="string", description="Token de acceso JWT"),
//  * @OA\Property(property="token_type", type="string", example="bearer"),
//  * @OA\Property(property="expires_in", type="integer", description="Tiempo de vida en segundos", example=3600)
//  * )
//  */
// class TokenResponseSchema {}