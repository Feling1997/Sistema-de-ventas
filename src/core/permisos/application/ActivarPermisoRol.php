<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Application;

use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;

final class ActivarPermisoRol
{
    public function __construct(private readonly PermisoRepository $permisoRepository)
    {
    }

    public function ejecutar(int $rolId, int $permisoId): bool
    {
        $activado = $this->permisoRepository->activarParaRol($rolId, $permisoId);

        return $activado;
    }
}
