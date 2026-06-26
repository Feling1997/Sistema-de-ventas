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
        Schema::connection($this->connection)->create('reparaciones_adjuntos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reparacion_id');
            $table->string('nombre', 180);
            $table->string('ruta', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->timestamps();
            $table->index('reparacion_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones_adjuntos');
    }
};
