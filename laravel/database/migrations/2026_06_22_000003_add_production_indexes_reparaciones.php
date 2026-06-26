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
        Schema::connection($this->connection)->table('reparaciones', function (Blueprint $table): void {
            $table->index('activo');
        });
        Schema::connection($this->connection)->table('reparaciones_equipos', function (Blueprint $table): void {
            $table->index('marca');
            $table->index('modelo');
        });
        Schema::connection($this->connection)->table('reparaciones_estados', function (Blueprint $table): void {
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('reparaciones', function (Blueprint $table): void {
            $table->dropIndex(['activo']);
        });
        Schema::connection($this->connection)->table('reparaciones_equipos', function (Blueprint $table): void {
            $table->dropIndex(['marca']);
            $table->dropIndex(['modelo']);
        });
        Schema::connection($this->connection)->table('reparaciones_estados', function (Blueprint $table): void {
            $table->dropIndex(['nombre']);
        });
    }
};
