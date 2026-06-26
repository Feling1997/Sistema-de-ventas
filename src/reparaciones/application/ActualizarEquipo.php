<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EquipoReparacionModel;

final class ActualizarEquipo
{
    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(int $id, array $datos): array
    {
        $equipo = EquipoReparacionModel::query()->where('id', $id)->first();
        $ok = false;

        if ($equipo instanceof EquipoReparacionModel) {
            $equipo->fill($this->datosPermitidos($datos));
            $ok = (bool) $equipo->save();
        }

        $resultado = ['ok' => $ok, 'id' => $id];

        return $resultado;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function datosPermitidos(array $datos): array
    {
        $permitidos = [];
        $campos = ['contacto_id', 'tipo', 'marca', 'modelo', 'serie', 'observaciones'];

        foreach ($campos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $permitidos[$campo] = $datos[$campo] === '' ? null : $datos[$campo];
            }
        }

        return $permitidos;
    }
}
