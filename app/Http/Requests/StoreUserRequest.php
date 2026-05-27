<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

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
