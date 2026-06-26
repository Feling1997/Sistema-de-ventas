<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;
use Ventas\Reparaciones\Infrastructure\Models\ReparacionModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListarReparaciones
{
    public function __construct(private readonly ContactoRepository $contactos)
    {
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function ejecutar(array $filtros = [], int $pagina = 1, int $limite = 20): array
    {
        $buscar = trim((string) ($filtros['q'] ?? ''));
        $query = ReparacionModel::query()
            ->with(['contacto', 'equipo', 'estado', 'ticket'])
            ->withCount('adjuntos')
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id');

        if ($buscar !== '') {
            $contactoIds = $this->contactoIds($buscar);
            $query->where(function ($consulta) use ($buscar, $contactoIds): void {
                $consulta->where('codigo', 'like', '%' . $buscar . '%')
                    ->orWhere('problema', 'like', '%' . $buscar . '%')
                    ->orWhere('diagnostico', 'like', '%' . $buscar . '%');

                if ($contactoIds !== []) {
                    $consulta->orWhereIn('contacto_id', $contactoIds);
                }
            });
        }

        if ((string) ($filtros['estado'] ?? '') !== '') {
            $estado = (string) $filtros['estado'];
            $query->whereHas('estado', static function ($consulta) use ($estado): void {
                $consulta->where('nombre', $estado);
            });
        }

        if ((string) ($filtros['activo'] ?? '') !== '') {
            $query->where('activo', $this->activo($filtros['activo']));
        }

        if ((string) ($filtros['fecha_desde'] ?? '') !== '') {
            $query->whereDate('fecha_ingreso', '>=', (string) $filtros['fecha_desde']);
        }

        if ((string) ($filtros['fecha_hasta'] ?? '') !== '') {
            $query->whereDate('fecha_ingreso', '<=', (string) $filtros['fecha_hasta']);
        }

        if ((int) ($filtros['contacto_id'] ?? 0) > 0) {
            $query->where('contacto_id', (int) $filtros['contacto_id']);
        }

        $paginador = $query->paginate(max(1, min(20, $limite)), ['*'], 'page', max(1, $pagina));
        $reparaciones = $this->serializarPaginador($paginador);
        $resultado = [
            'data' => $reparaciones,
            'pagination' => [
                'current_page' => $paginador->currentPage(),
                'last_page' => $paginador->lastPage(),
                'per_page' => $paginador->perPage(),
                'total' => $paginador->total(),
            ],
        ];

        return $resultado;
    }

    private function activo(mixed $valor): bool
    {
        $activo = in_array((string) $valor, ['1', 'true', 'SI', 'si', 'activo'], true);

        return $activo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializarPaginador(LengthAwarePaginator $paginador): array
    {
        $reparaciones = $paginador->getCollection()
            ->map(static fn (ReparacionModel $reparacion): array => [
                'id' => $reparacion->id,
                'codigo' => $reparacion->codigo,
                'contacto_id' => $reparacion->contacto_id,
                'cliente' => trim((string) ($reparacion->contacto?->nombre . ' ' . $reparacion->contacto?->apellido)),
                'telefono' => $reparacion->contacto?->telefono,
                'equipo_id' => $reparacion->equipo_id,
                'equipo' => trim((string) ($reparacion->equipo?->marca . ' ' . $reparacion->equipo?->modelo)),
                'estado_id' => $reparacion->estado_id,
                'estado' => $reparacion->estado?->nombre,
                'problema' => $reparacion->problema,
                'diagnostico' => $reparacion->diagnostico,
                'garantia' => $reparacion->garantia,
                'precio' => $reparacion->precio,
                'fecha_ingreso' => $reparacion->fecha_ingreso,
                'fecha_entrega' => $reparacion->fecha_entrega,
                'activo' => $reparacion->activo,
                'ticket_id' => $reparacion->ticket?->id,
                'adjuntos_count' => $reparacion->adjuntos_count,
            ])
            ->all();

        return $reparaciones;
    }

    /**
     * @return array<int, int>
     */
    private function contactoIds(string $buscar): array
    {
        $ids = [];

        foreach ($this->contactos->autocompletar($buscar) as $contacto) {
            if ($contacto->id() !== null) {
                $ids[] = $contacto->id();
            }
        }

        return $ids;
    }
}
