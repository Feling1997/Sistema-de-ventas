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
        Schema::connection($this->connection)->create('reparaciones', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 80)->nullable()->unique();
            $table->unsignedBigInteger('contacto_id');
            $table->unsignedBigInteger('equipo_id')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->text('problema');
            $table->text('diagnostico')->nullable();
            $table->string('garantia', 120)->nullable();
            $table->decimal('precio', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_ingreso')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index('contacto_id');
            $table->index('equipo_id');
            $table->index('estado_id');
            $table->index('fecha_ingreso');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones');
    }
};
