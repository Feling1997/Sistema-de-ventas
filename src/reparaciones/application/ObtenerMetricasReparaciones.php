<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AuditoriaReparacionModel;
use Carbon\CarbonImmutable;

final class ObtenerMetricasReparaciones
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $eventos = AuditoriaReparacionModel::query()->orderBy('accion')->get(['accion', 'tiempo_ms', 'resultado']);
        $desde = CarbonImmutable::now()->subDay();
        $metricas = $eventos
            ->groupBy('accion')
            ->map(fn ($grupo, string $accion): array => $this->serializar($accion, $grupo->pluck('tiempo_ms')->map(static fn ($valor): int => (int) $valor)->all()))
            ->values()
            ->all();
        $resultado = [
            'total_operaciones' => $eventos->count(),
            'operaciones_exitosas' => $eventos->where('resultado', 'ok')->count(),
            'operaciones_fallidas' => $eventos->where('resultado', '!=', 'ok')->count(),
            'errores' => $eventos->where('resultado', '!=', 'ok')->count(),
            'ultimas_24_horas' => AuditoriaReparacionModel::query()->where('created_at', '>=', $desde)->count(),
            'metricas' => $metricas,
        ];

        return $resultado;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializar(string $accion, array $tiempos): array
    {
        $total = count($tiempos);
        $promedio = $total > 0 ? array_sum($tiempos) / $total : 0;
        $datos = [
            'accion' => $accion,
            'total' => $total,
            'promedio_ms' => round($promedio, 2),
            'p95_ms' => $this->percentil($tiempos, 95),
            'min_ms' => $total > 0 ? min($tiempos) : 0,
            'max_ms' => $total > 0 ? max($tiempos) : 0,
            'clasificacion' => $this->clasificar($promedio),
        ];

        return $datos;
    }

    private function clasificar(float $promedio): string
    {
        $clasificacion = 'LENTO';

        if ($promedio < 250) {
            $clasificacion = 'EXCELENTE';
        } elseif ($promedio < 600) {
            $clasificacion = 'BUENO';
        } elseif ($promedio < 1200) {
            $clasificacion = 'ACEPTABLE';
        }

        return $clasificacion;
    }

    /**
     * @param array<int, int> $tiempos
     */
    private function percentil(array $tiempos, int $percentil): int
    {
        $valor = 0;
        $total = count($tiempos);

        if ($total > 0) {
            sort($tiempos);
            $indice = (int) ceil(($percentil / 100) * $total) - 1;
            $valor = $tiempos[max(0, min($total - 1, $indice))];
        }

        return $valor;
    }
}
