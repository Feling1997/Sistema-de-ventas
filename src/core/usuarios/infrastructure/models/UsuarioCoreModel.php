<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ventas\Core\Roles\Infrastructure\Models\RolModel;

final class UsuarioCoreModel extends Model
{
    protected $connection = 'sistema_core';

    protected $table = 'usuarios';

    protected $fillable = [
        'usuario_legacy_id',
        'nombre',
        'usuario',
        'email',
        'clave',
        'activo',
        'ultimo_acceso',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_acceso' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RolModel::class, 'usuario_rol', 'usuario_id', 'rol_id')->withTimestamps();
    }
}
