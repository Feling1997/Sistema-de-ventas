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
        Schema::connection($this->connection)->create('reparaciones_equipos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contacto_id');
            $table->string('tipo', 80);
            $table->string('marca', 120)->nullable();
            $table->string('modelo', 120)->nullable();
            $table->string('serie', 120)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index('contacto_id');
            $table->index('serie');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones_equipos');
    }
};
