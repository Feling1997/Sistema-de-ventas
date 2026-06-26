<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;

final class BuscarReparacion
{
    /**
     * @return array<string, mixed>|null
     */
    public function ejecutar(int $id): ?array
    {
        $reparacion = null;
        $modelo = ReparacionModel::query()->with(['contacto', 'equipo', 'estado'])->where('id', $id)->first();

        if ($modelo instanceof ReparacionModel) {
            $reparacion = $this->serializar($modelo);
        }

        return $reparacion;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(ReparacionModel $reparacion): array
    {
        $datos = [
            'id' => $reparacion->id,
            'codigo' => $reparacion->codigo,
            'contacto_id' => $reparacion->contacto_id,
            'contacto' => $reparacion->contacto?->toArray(),
            'equipo_id' => $reparacion->equipo_id,
            'equipo' => $reparacion->equipo?->toArray(),
            'estado_id' => $reparacion->estado_id,
            'estado' => $reparacion->estado?->toArray(),
            'problema' => $reparacion->problema,
            'diagnostico' => $reparacion->diagnostico,
            'garantia' => $reparacion->garantia,
            'precio' => $reparacion->precio,
            'observaciones' => $reparacion->observaciones,
            'fecha_ingreso' => $reparacion->fecha_ingreso,
            'fecha_entrega' => $reparacion->fecha_entrega,
            'activo' => $reparacion->activo,
        ];

        return $datos;
    }
}
