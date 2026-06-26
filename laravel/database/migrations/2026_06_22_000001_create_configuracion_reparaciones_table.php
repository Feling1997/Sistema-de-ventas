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
        Schema::connection($this->connection)->create('configuracion_reparaciones', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 120)->unique();
            $table->text('valor')->nullable();
            $table->string('tipo', 40)->default('string');
            $table->string('grupo', 80)->default('general');
            $table->timestamps();
            $table->index('grupo');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('configuracion_reparaciones');
    }
};
