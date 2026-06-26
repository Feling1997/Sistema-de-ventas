<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class ConfiguracionReparacionesModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'configuracion_reparaciones';

    /** @var array<int, string> */
    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'grupo',
    ];
}
