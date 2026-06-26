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
        Schema::connection($this->connection)->create('reparaciones_tickets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('reparacion_id');
            $table->string('codigo', 80);
            $table->timestamp('emitido_en')->nullable();
            $table->timestamps();
            $table->unique('codigo');
            $table->index('reparacion_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('reparaciones_tickets');
    }
};
