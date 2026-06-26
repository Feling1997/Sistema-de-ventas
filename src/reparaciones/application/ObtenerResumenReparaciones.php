<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EstadoReparacionModel;
use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;
use Illuminate\Support\Facades\Cache;

final class ObtenerResumenReparaciones
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $resumen = Cache::remember('reparaciones.resumen', 300, function (): array {
            $estados = EstadoReparacionModel::query()->pluck('id', 'nombre')->all();
            $datos = [
                'total' => ReparacionModel::query()->count(),
                'activas' => ReparacionModel::query()->where('activo', true)->count(),
                'inactivas' => ReparacionModel::query()->where('activo', false)->count(),
                'pendientes' => $this->contarEstado($estados, 'PENDIENTE'),
                'en_reparacion' => $this->contarEstado($estados, 'EN_REPARACION'),
                'reparadas' => $this->contarEstado($estados, 'REPARADO'),
                'entregadas' => $this->contarEstado($estados, 'ENTREGADO'),
                'canceladas' => $this->contarEstado($estados, 'CANCELADO'),
                'total_presupuestado' => (float) ReparacionModel::query()->where('activo', true)->sum('precio'),
            ];

            return $datos;
        });

        return $resumen;
    }

    /**
     * @param array<string, int> $estados
     */
    private function contarEstado(array $estados, string $nombre): int
    {
        $cantidad = 0;

        if (isset($estados[$nombre])) {
            $cantidad = ReparacionModel::query()->where('estado_id', (int) $estados[$nombre])->count();
        }

        return $cantidad;
    }
}
