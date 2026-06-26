<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sistema_reparaciones';

    public function up(): void
    {
        Schema::connection($this->connection)->create('reparaciones_auditoria', function (Blueprint $table): void {
            $table->id();
            $table->string('accion', 80);
            $table->string('usuario', 120)->nullable();
            $table->unsignedBigInteger('reparacion_id')->nullable();
            $table->unsignedInteger('tiempo_ms')->default(0);
            $table->string('resultado', 40)->default('ok');
            $table->string('severidad', 40)->default('bajo');
            $table->string('mensaje', 255)->nullable();
            $table->timestamps();
            $table->index('accion');
            $table->index('resultado');
            $table->index('severidad');
            $table->index('reparacion_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones_auditoria');
    }
};
