<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('sistema_core')->hasTable('roles')) {
            Schema::connection('sistema_core')->create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre')->unique();
                $table->string('descripcion')->nullable();
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::connection('sistema_core')->hasTable('permisos')) {
            Schema::connection('sistema_core')->create('permisos', function (Blueprint $table): void {
                $table->id();
                $table->string('modulo')->index();
                $table->string('accion')->index();
                $table->string('codigo')->unique();
                $table->string('descripcion')->nullable();
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::connection('sistema_core')->hasTable('usuarios')) {
            Schema::connection('sistema_core')->create('usuarios', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('usuario_legacy_id')->nullable()->unique();
                $table->string('nombre')->nullable();
                $table->string('usuario')->unique();
                $table->string('email')->nullable();
                $table->string('clave')->nullable();
                $table->boolean('activo')->default(true)->index();
                $table->timestamp('ultimo_acceso')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('sistema_core')->hasTable('rol_permiso')) {
            Schema::connection('sistema_core')->create('rol_permiso', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('permiso_id')->constrained('permisos')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['rol_id', 'permiso_id']);
            });
        }

        if (!Schema::connection('sistema_core')->hasTable('usuario_rol')) {
            Schema::connection('sistema_core')->create('usuario_rol', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
                $table->foreignId('rol_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['usuario_id', 'rol_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('sistema_core')->dropIfExists('usuario_rol');
        Schema::connection('sistema_core')->dropIfExists('rol_permiso');
        Schema::connection('sistema_core')->dropIfExists('usuarios');
        Schema::connection('sistema_core')->dropIfExists('permisos');
        Schema::connection('sistema_core')->dropIfExists('roles');
    }
};
