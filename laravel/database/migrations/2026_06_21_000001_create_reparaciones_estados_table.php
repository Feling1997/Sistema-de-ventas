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
        Schema::connection($this->connection)->create('reparaciones_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('finaliza')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones_estados');
    }
};
