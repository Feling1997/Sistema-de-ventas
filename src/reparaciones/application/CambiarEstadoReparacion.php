<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EstadoReparacionModel;
use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;

final class CambiarEstadoReparacion
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(int $id, int $estadoId): array
    {
        $reparacion = ReparacionModel::query()->where('id', $id)->first();
        $estado = EstadoReparacionModel::query()->where('id', $estadoId)->where('activo', true)->first();
        $ok = false;

        if ($reparacion instanceof ReparacionModel && $estado instanceof EstadoReparacionModel) {
            $reparacion->estado_id = $estado->id;
            $ok = (bool) $reparacion->save();
        }

        $resultado = [
            'ok' => $ok,
            'id' => $id,
            'estado_id' => $estadoId,
        ];

        return $resultado;
    }
}
