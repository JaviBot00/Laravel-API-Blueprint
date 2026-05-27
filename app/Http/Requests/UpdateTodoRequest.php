<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateTodoRequest",
 *   @OA\Property(property="title",     type="string",  example="Estudiar Laravel (actualizado)"),
 *   @OA\Property(property="completed", type="boolean", example=true)
 * )
 */
class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'sometimes' significa "valida este campo solo si viene en la petición"
            // Útil para actualizaciones parciales (PATCH) donde no todos los campos son obligatorios
            'title'     => ['sometimes', 'string', 'max:255'],
            'completed' => ['sometimes', 'boolean'],
        ];
    }
}
