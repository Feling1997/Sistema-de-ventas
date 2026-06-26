<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class AdjuntoReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones_adjuntos';

    /** @var array<int, string> */
    protected $fillable = [
        'reparacion_id',
        'nombre',
        'ruta',
        'miniatura',
        'mime',
        'tamano',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tamano' => 'integer',
    ];
}
