<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Application;

use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;

final class QuitarPermisoRol
{
    public function __construct(private readonly PermisoRepository $permisoRepository)
    {
    }

    public function ejecutar(int $rolId, int $permisoId): bool
    {
        $quitado = $this->permisoRepository->quitarDeRol($rolId, $permisoId);

        return $quitado;
    }
}
