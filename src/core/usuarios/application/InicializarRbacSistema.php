<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Application;

use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;
use Ventas\Core\Roles\Domain\Repositorios\RolRepository;

final class InicializarRbacSistema
{
    public function __construct(
        private readonly RolRepository $rolRepository,
        private readonly PermisoRepository $permisoRepository
    ) {
    }

    public function ejecutar(): void
    {
        $this->rolRepository->asegurarIniciales();
        $this->permisoRepository->asegurarIniciales();
    }
}
