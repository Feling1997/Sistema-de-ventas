<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class EquipoReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones_equipos';

    /** @var array<int, string> */
    protected $fillable = [
        'contacto_id',
        'tipo',
        'marca',
        'modelo',
        'serie',
        'observaciones',
    ];
}
