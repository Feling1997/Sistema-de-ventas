<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

final class TicketReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones_tickets';

    /** @var array<int, string> */
    protected $fillable = [
        'reparacion_id',
        'codigo',
        'emitido_en',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'emitido_en' => 'datetime',
    ];
}
