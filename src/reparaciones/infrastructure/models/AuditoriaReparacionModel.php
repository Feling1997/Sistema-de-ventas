<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditoriaReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones_auditoria';

    /** @var array<int, string> */
    protected $fillable = [
        'accion',
        'usuario',
        'reparacion_id',
        'tiempo_ms',
        'resultado',
        'severidad',
        'mensaje',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'reparacion_id' => 'integer',
        'tiempo_ms' => 'integer',
    ];
}
