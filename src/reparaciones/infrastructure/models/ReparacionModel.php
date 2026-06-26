<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Infrastructure\Models;

use Ventas\Core\Contactos\Infrastructure\Models\ContactoModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

final class ReparacionModel extends Model
{
    protected $connection = 'sistema_reparaciones';

    protected $table = 'reparaciones';

    /** @var array<int, string> */
    protected $fillable = [
        'codigo',
        'contacto_id',
        'equipo_id',
        'estado_id',
        'problema',
        'diagnostico',
        'garantia',
        'precio',
        'observaciones',
        'fecha_ingreso',
        'fecha_entrega',
        'activo',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
        'fecha_ingreso' => 'datetime',
        'fecha_entrega' => 'datetime',
    ];

    public function contacto(): BelongsTo
    {
        $relacion = $this->belongsTo(ContactoModel::class, 'contacto_id');

        return $relacion;
    }

    public function equipo(): BelongsTo
    {
        $relacion = $this->belongsTo(EquipoReparacionModel::class, 'equipo_id');

        return $relacion;
    }

    public function estado(): BelongsTo
    {
        $relacion = $this->belongsTo(EstadoReparacionModel::class, 'estado_id');

        return $relacion;
    }

    public function ticket(): HasOne
    {
        $relacion = $this->hasOne(TicketReparacionModel::class, 'reparacion_id');

        return $relacion;
    }

    public function adjuntos(): HasMany
    {
        $relacion = $this->hasMany(AdjuntoReparacionModel::class, 'reparacion_id');

        return $relacion;
    }
}
