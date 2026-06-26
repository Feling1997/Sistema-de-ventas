<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;
use Illuminate\Support\Facades\Cache;

final class ActualizarReparacion
{
    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(int $id, array $datos): array
    {
        $modelo = ReparacionModel::query()->where('id', $id)->first();
        $actualizado = false;

        if ($modelo instanceof ReparacionModel) {
            $modelo->fill($this->datosPermitidos($datos));
            $actualizado = (bool) $modelo->save();
            Cache::forget('reparaciones.resumen');
        }

        $resultado = [
            'ok' => $actualizado,
            'id' => $id,
        ];

        return $resultado;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function datosPermitidos(array $datos): array
    {
        $permitidos = [];
        $campos = [
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

        foreach ($campos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $permitidos[$campo] = $datos[$campo] === '' ? null : $datos[$campo];
            }
        }

        return $permitidos;
    }
}
