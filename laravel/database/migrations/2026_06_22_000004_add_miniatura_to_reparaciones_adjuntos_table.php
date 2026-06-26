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
        Schema::connection($this->connection)->table('reparaciones_adjuntos', function (Blueprint $table): void {
            $table->string('miniatura', 255)->nullable()->after('ruta');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('reparaciones_adjuntos', function (Blueprint $table): void {
            $table->dropColumn('miniatura');
        });
    }
};
