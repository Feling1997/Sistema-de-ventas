<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class PermisoModel extends Model
{
    protected $connection = 'sistema_core';

    protected $table = 'permisos';

    protected $fillable = ['modulo', 'accion', 'codigo', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
