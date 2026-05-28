<?php

return [

    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Laravel API Blueprint',
            ],

            'routes' => [
                /*
                 * Ruta donde se servirá la UI de Swagger.
                 * Accesible en: https://tu-dominio.com/api/documentation
                 */
                'api' => 'api/documentation',
            ],

            'paths' => [
                /*
                 * use_absolute_path = false es necesario cuando hay un reverse
                 * proxy (como Nginx Proxy Manager) por delante. Con true,
                 * Swagger construye URLs absolutas usando el host interno del
                 * contenedor en lugar del dominio público, rompiendo los assets
                 * de la UI (CSS/JS) y las llamadas de "Try it out".
                 */
                'use_absolute_path' => false,

                'docs_json'              => 'api-docs.json',
                'docs_yaml'              => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Carpeta donde swagger-php escanea las anotaciones @OA\.
                 * Apuntamos a app/ completo para que encuentre OpenApiSpec.php
                 * y todos los controladores.
                 */
                'annotations' => [
                    base_path('app'),
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs'            => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api'             => [],
                'asset'           => [],
                'docs'            => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs'    => storage_path('api-docs'),
            'views'   => base_path('resources/views/vendor/l5-swagger'),
            'base'    => env('L5_SWAGGER_BASE_PATH', null),
            'excludes' => [],
        ],

        'scanOptions' => [
            'analyser'              => null,
            'analysis'              => null,
            'processors'            => [],
            'pattern'               => null,
            'exclude'               => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security'        => [],
        ],

        /*
         * generate_always = false en producción.
         * Swagger solo se regenera ejecutando: php artisan l5-swagger:generate
         * (lo hace deploy.sh). Con true se regenera en cada petición HTTP,
         * escaneando toda la carpeta app/ — innecesario y costoso.
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        'generate_yaml_copy'    => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy'                 => false,
        'additional_config_url' => null,
        'operations_sort'       => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url'         => null,

        'ui' => [
            'display' => [
                'doc_expansion'          => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter'                 => env('L5_SWAGGER_UI_FILTERS', true),
                'show_extensions'        => env('L5_SWAGGER_UI_SHOW_EXTENSIONS', false),
                'show_common_extensions' => env('L5_SWAGGER_UI_SHOW_COMMON_EXTENSIONS', false),
                'try_it_out_enabled'     => env('L5_SWAGGER_UI_TRY_IT_OUT_ENABLED', true),
            ],
            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            /*
             * URL pública de la API — la usa @OA\Server en OpenApiSpec.php.
             * En producción debe valer https://laravel.diputacion.malaga.es
             * En desarrollo local puede valer http://localhost:8080
             * Configúrala en el .env con la clave L5_SWAGGER_CONST_HOST.
             */
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'https://laravel.diputacion.malaga.es'),
        ],
    ],
];
