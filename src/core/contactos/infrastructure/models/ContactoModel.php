<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class ContactoModel extends Model
{
    protected $connection = 'sistema_core';

    protected $table = 'contactos';

    /** @var array<int, string> */
    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'correo',
        'documento',
        'direccion',
        'observaciones',
        'activo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'activo' => 'boolean',
    ];
}
