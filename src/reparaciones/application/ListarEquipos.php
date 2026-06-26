<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EquipoReparacionModel;

final class ListarEquipos
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(int $limite = 20, string $buscar = ''): array
    {
        $query = EquipoReparacionModel::query();

        if ($buscar !== '') {
            $query->where(function ($consulta) use ($buscar): void {
                $consulta->where('marca', 'like', '%' . $buscar . '%')
                    ->orWhere('modelo', 'like', '%' . $buscar . '%')
                    ->orWhere('serie', 'like', '%' . $buscar . '%')
                    ->orWhere('tipo', 'like', '%' . $buscar . '%');
            });
        }

        $equipos = $query
            ->orderByDesc('id')
            ->limit(max(1, min(20, $limite)))
            ->get()
            ->map(static fn (EquipoReparacionModel $equipo): array => [
                'id' => $equipo->id,
                'contacto_id' => $equipo->contacto_id,
                'tipo' => $equipo->tipo,
                'marca' => $equipo->marca,
                'modelo' => $equipo->modelo,
                'serie' => $equipo->serie,
                'observaciones' => $equipo->observaciones,
            ])
            ->all();

        return $equipos;
    }
}
