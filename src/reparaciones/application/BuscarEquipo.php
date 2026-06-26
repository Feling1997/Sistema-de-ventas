<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EquipoReparacionModel;

final class BuscarEquipo
{
    /**
     * @return array<string, mixed>|null
     */
    public function ejecutar(int $id): ?array
    {
        $equipo = null;
        $modelo = EquipoReparacionModel::query()->where('id', $id)->first();

        if ($modelo instanceof EquipoReparacionModel) {
            $equipo = [
                'id' => $modelo->id,
                'contacto_id' => $modelo->contacto_id,
                'tipo' => $modelo->tipo,
                'marca' => $modelo->marca,
                'modelo' => $modelo->modelo,
                'serie' => $modelo->serie,
                'observaciones' => $modelo->observaciones,
            ];
        }

        return $equipo;
    }
}
