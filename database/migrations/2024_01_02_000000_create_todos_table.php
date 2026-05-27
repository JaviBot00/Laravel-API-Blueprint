<?php

// =============================================================================
// MIGRATION: create_todos_table
// Archivo: database/migrations/2024_01_02_000000_create_todos_table.php
// =============================================================================
// NOTA: En Laravel, cada migración es un archivo separado. Los agrupamos
// aquí con comentarios para facilitar la lectura en el repo de referencia.
// En el proyecto real, cada bloque es un archivo independiente.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// -----------------------------------------------------------------------------
// Tabla: todos
// -----------------------------------------------------------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            // boolean con valor por defecto false
            $table->boolean('completed')->default(false);

            // Clave foránea hacia users. onDelete('cascade') significa que si
            // se borra el usuario, se borran también sus tareas automáticamente.
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // created_at y updated_at — Eloquent los gestiona automáticamente
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }
};
