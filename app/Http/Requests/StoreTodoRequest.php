<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Validación para crear una nueva tarea.
 *
 * Al extraer la validación del controlador a esta clase, conseguimos:
 *   1. Controladores más limpios y legibles.
 *   2. Reutilizar la validación si se llama desde otro sitio.
 *   3. Poder sobreescribir los mensajes de error fácilmente.
 *
 * @OA\Schema(
 *   schema="StoreTodoRequest",
 *   required={"title"},
 *   @OA\Property(property="title",     type="string",  example="Estudiar Laravel"),
 *   @OA\Property(property="completed", type="boolean", example=false)
 * )
 */

#[OA\Schema(
  schema: "StoreTodoRequest",
  required: ["title"],
  properties: [
    new OA\Property(property: "title", type: "string", example: "Estudiar Laravel"),
    new OA\Property(property: "completed", type: "boolean", example: false)
  ]
)]
class StoreTodoRequest extends FormRequest
{
    /**
     * ¿Quién puede hacer esta petición?
     * Devolver true significa "cualquier usuario autenticado".
     * La autorización real (roles/permisos) se gestiona en la Policy y en las rutas.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación de los datos entrantes.
     */
    public function rules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255'],
            'completed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la tarea es obligatorio.',
            'title.max'      => 'El título no puede superar los 255 caracteres.',
            'completed.boolean' => 'El campo completado debe ser verdadero o falso.',
        ];
    }
}
