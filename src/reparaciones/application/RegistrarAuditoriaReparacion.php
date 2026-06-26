<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Infrastructure\Models\AuditoriaReparacionModel;

final class RegistrarAuditoriaReparacion
{
    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $auditoria = AuditoriaReparacionModel::query()->create([
            'accion' => (string) ($datos['accion'] ?? 'sin_accion'),
            'usuario' => $this->usuario($datos['usuario'] ?? null),
            'reparacion_id' => $this->reparacionId($datos['reparacion_id'] ?? null),
            'tiempo_ms' => max(0, (int) ($datos['tiempo_ms'] ?? 0)),
            'resultado' => (string) ($datos['resultado'] ?? 'ok'),
            'severidad' => (string) ($datos['severidad'] ?? 'bajo'),
            'mensaje' => $this->mensaje($datos['mensaje'] ?? null),
        ]);
        $resultado = ['ok' => true, 'id' => $auditoria->id];

        return $resultado;
    }

    private function usuario(mixed $usuario): ?string
    {
        $valor = null;
        $texto = trim((string) ($usuario ?? ''));

        if ($texto !== '') {
            $valor = mb_substr($texto, 0, 120);
        }

        return $valor;
    }

    private function reparacionId(mixed $reparacionId): ?int
    {
        $id = null;
        $valor = (int) ($reparacionId ?? 0);

        if ($valor > 0) {
            $id = $valor;
        }

        return $id;
    }

    private function mensaje(mixed $mensaje): ?string
    {
        $valor = null;
        $texto = trim((string) ($mensaje ?? ''));

        if ($texto !== '') {
            $valor = mb_substr($texto, 0, 255);
        }

        return $valor;
    }
}
