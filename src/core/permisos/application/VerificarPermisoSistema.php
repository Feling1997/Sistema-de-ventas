<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Application;

use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;

final class VerificarPermisoSistema
{
    public function __construct(private readonly PermisoRepository $permisoRepository)
    {
    }

    public function ejecutar(int $usuarioLegacyId, string $codigo): bool
    {
        $permitido = $this->permisoRepository->usuarioTienePermiso($usuarioLegacyId, $codigo);

        return $permitido;
    }
}
