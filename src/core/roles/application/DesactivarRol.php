<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Application;

use Ventas\Core\Roles\Domain\Repositorios\RolRepository;

final class DesactivarRol
{
    public function __construct(private readonly RolRepository $rolRepository)
    {
    }

    public function ejecutar(int $id): bool
    {
        $desactivado = $this->rolRepository->desactivar($id);

        return $desactivado;
    }
}
