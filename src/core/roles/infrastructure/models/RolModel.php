<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Infrastructure\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Ventas\Core\Permisos\Infrastructure\Models\PermisoModel;

final class RolModel extends Model
{
    protected $connection = 'sistema_core';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(PermisoModel::class, 'rol_permiso', 'rol_id', 'permiso_id')->withTimestamps();
    }
}
