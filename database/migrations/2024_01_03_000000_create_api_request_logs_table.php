<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// -----------------------------------------------------------------------------
// Tabla: api_request_logs
// Registra cada petición a la API para las estadísticas de uso.
// Esta tabla crece rápido en producción. En un sistema real se recomienda
// archivar o purgar registros con más de X días mediante un comando artisan.
// -----------------------------------------------------------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();

            // nullable porque puede haber peticiones sin autenticar (ej: /login)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');

            $table->string('method', 10);       // GET, POST, PUT, DELETE...
            $table->string('path');             // /api/todos, /api/users...
            $table->smallInteger('status_code');// 200, 201, 404, 422...
            $table->integer('response_time');   // milisegundos
            $table->string('ip_address', 45);  // IPv4 o IPv6

            // Solo created_at, sin updated_at (los logs no se modifican)
            $table->timestamp('created_at')->useCurrent();

            // Índices para acelerar las queries de estadísticas
            $table->index('user_id');
            $table->index('path');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
