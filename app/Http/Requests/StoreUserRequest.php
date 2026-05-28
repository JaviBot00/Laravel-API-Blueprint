<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * @OA\Schema(
 * schema="StoreUserRequest",
 * required={"name", "email", "password", "role"},
 * @OA\Property(property="name", type="string", example="Ada Lovelace"),
 * @OA\Property(property="email", type="string", format="email", example="ada@example.com"),
 * @OA\Property(property="password", type="string", format="password", example="password123"),
 * @OA\Property(property="role", type="string", example="user", enum={"admin", "user"})
 * )
 */

#[OA\Schema(
  schema: "StoreUserRequest",
  required: ["name", "email", "password", "role"],
  properties: [
    new OA\Property(property: "name", type: "string", example: "Ada Lovelace"),
    new OA\Property(property: "email", type: "string", format: "email", example: "ada@example.com"),
    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
    new OA\Property(property: "role", type: "string", example: "user", enum: ["admin", "user"])
  ]
)]
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            // 'unique:users' comprueba que no exista ya ese email en la tabla users
            'email'    => ['required', 'email', 'unique:users,email'],
            // Password::min(8) es un helper de Laravel para reglas de contraseña
            'password' => ['required', Password::min(8)],
            // 'exists:roles,name' comprueba que el rol exista en la tabla roles de Spatie
            'role'     => ['required', 'string', 'exists:roles,name'],
        ];
    }
}
