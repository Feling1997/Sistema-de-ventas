<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sistema_core')->create('contactos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->string('documento')->nullable();
            $table->string('direccion')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('telefono');
            $table->index('documento');
            $table->index('nombre');
            $table->index('apellido');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::connection('sistema_core')->dropIfExists('contactos');
    }
};
