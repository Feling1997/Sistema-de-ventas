<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;
use Ventas\Reparaciones\Infrastructure\Models\EquipoReparacionModel;
use Ventas\Reparaciones\Infrastructure\Models\EstadoReparacionModel;
use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;
use Illuminate\Support\Facades\Cache;

final class CrearReparacion
{
    public function __construct(private readonly ContactoRepository $contactos)
    {
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $contactoId = (int) ($datos['contacto_id'] ?? 0);
        $equipoId = isset($datos['equipo_id']) && (int) $datos['equipo_id'] > 0 ? (int) $datos['equipo_id'] : null;
        $estadoId = (int) ($datos['estado_id'] ?? 0);
        $contacto = $this->contactos->buscarPorId($contactoId);
        $equipo = $equipoId !== null ? EquipoReparacionModel::query()->where('id', $equipoId)->first() : null;
        $estado = $estadoId > 0 ? EstadoReparacionModel::query()->where('id', $estadoId)->first() : null;
        $estadoFinal = $estado instanceof EstadoReparacionModel ? $estado : EstadoReparacionModel::query()->firstOrCreate(
            ['nombre' => 'PENDIENTE'],
            ['orden' => 1, 'finaliza' => false, 'activo' => true]
        );
        $equipoValido = $equipoId === null || $equipo instanceof EquipoReparacionModel;
        $estadoValido = $estadoId === 0 || $estado instanceof EstadoReparacionModel;
        $resultado = ['ok' => false, 'mensaje' => 'Contacto, equipo o estado invalido.'];

        if ($contacto instanceof Contacto && $equipoValido && $estadoValido) {
            $reparacion = ReparacionModel::query()->create([
                'codigo' => $this->codigo(),
                'contacto_id' => $contactoId,
                'equipo_id' => $equipoId,
                'estado_id' => $estadoFinal->id,
                'problema' => trim((string) ($datos['problema'] ?? 'Sin problema registrado')),
                'diagnostico' => $this->nullable($datos['diagnostico'] ?? null),
                'garantia' => $this->nullable($datos['garantia'] ?? null),
                'precio' => (float) ($datos['precio'] ?? 0),
                'observaciones' => $this->nullable($datos['observaciones'] ?? null),
                'fecha_ingreso' => $this->nullable($datos['fecha_ingreso'] ?? null),
                'fecha_entrega' => $this->nullable($datos['fecha_entrega'] ?? null),
                'activo' => true,
            ]);
            $resultado = ['ok' => true, 'id' => $reparacion->id, 'codigo' => $reparacion->codigo];
            Cache::forget('reparaciones.resumen');
        }

        return $resultado;
    }

    private function codigo(): string
    {
        $codigo = 'REP-' . date('Ymd-His') . '-' . random_int(1000, 9999);

        return $codigo;
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
