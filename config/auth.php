<?php

/*
|--------------------------------------------------------------------------
| config/auth.php
|--------------------------------------------------------------------------
|
| Configuración de autenticación de Laravel.
|
| El cambio clave respecto a la configuración por defecto es el guard 'api':
| en lugar de usar el driver 'token' (API keys simples), usamos 'jwt'
| del paquete tymon/jwt-auth.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Default Authentication Guard
    |--------------------------------------------------------------------------
    | Para una API pura usamos 'api' como guard por defecto.
    | Así Auth::user() resuelve automáticamente el usuario JWT
    | sin necesidad de especificar Auth::guard('api')->user() cada vez.
    */
    'defaults' => [
        'guard'     => 'api',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    | 'web'  → sesiones (para apps web tradicionales, no lo usamos)
    | 'api'  → JWT stateless (nuestro guard principal)
    */
    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver'   => 'jwt',      // ← driver de tymon/jwt-auth
            'provider' => 'users',    // ← usa la tabla 'users' para buscar el usuario
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    | Define cómo Laravel busca los usuarios en la base de datos.
    | 'model' apunta a la clase Eloquent que implementa JWTSubject.
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    | Configuración para el sistema de recuperación de contraseñas.
    | No es necesario para la API, pero Laravel lo requiere en el archivo.
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
