<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class EstadoReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones_estados';

    /** @var array<int, string> */
    protected $fillable = [
        'nombre',
        'orden',
        'finaliza',
        'activo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'orden' => 'integer',
        'finaliza' => 'boolean',
        'activo' => 'boolean',
    ];
}
