<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\EstadoReparacionModel;
use Illuminate\Support\Facades\Cache;

final class ListarEstadosReparacion
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function ejecutar(): array
    {
        $this->asegurarEstados();
        $estados = Cache::remember('reparaciones.estados', 300, static fn (): array => EstadoReparacionModel::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(static fn (EstadoReparacionModel $estado): array => [
                'id' => $estado->id,
                'nombre' => $estado->nombre,
                'orden' => $estado->orden,
                'finaliza' => $estado->finaliza,
                'activo' => $estado->activo,
            ])
            ->all());

        return $estados;
    }

    private function asegurarEstados(): void
    {
        $creado = false;
        $estados = [
            ['nombre' => 'PENDIENTE', 'orden' => 1, 'finaliza' => false],
            ['nombre' => 'EN_REPARACION', 'orden' => 2, 'finaliza' => false],
            ['nombre' => 'REPARADO', 'orden' => 3, 'finaliza' => false],
            ['nombre' => 'ENTREGADO', 'orden' => 4, 'finaliza' => true],
            ['nombre' => 'CANCELADO', 'orden' => 5, 'finaliza' => true],
        ];

        foreach ($estados as $estado) {
            $modelo = EstadoReparacionModel::query()->firstOrCreate(
                ['nombre' => $estado['nombre']],
                ['orden' => $estado['orden'], 'finaliza' => $estado['finaliza'], 'activo' => true]
            );
            if ($modelo->wasRecentlyCreated) {
                $creado = true;
            }
        }

        if ($creado) {
            Cache::forget('reparaciones.estados');
        }
    }
}
