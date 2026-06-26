<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EquipoReparacionModel;

final class AgregarEquipo
{
    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $equipo = EquipoReparacionModel::query()->create([
            'contacto_id' => (int) ($datos['contacto_id'] ?? 0),
            'tipo' => trim((string) ($datos['tipo'] ?? 'Telefono')),
            'marca' => $this->nullable($datos['marca'] ?? null),
            'modelo' => $this->nullable($datos['modelo'] ?? null),
            'serie' => $this->nullable($datos['serie'] ?? null),
            'observaciones' => $this->nullable($datos['observaciones'] ?? null),
        ]);
        $resultado = [
            'ok' => true,
            'id' => $equipo->id,
        ];

        return $resultado;
    }

    private function nullable(mixed $valor): ?string
    {
        $normalizado = null;
        $texto = trim((string) ($valor ?? ''));

        if ($texto !== '') {
            $normalizado = $texto;
        }

        return $normalizado;
    }
}
