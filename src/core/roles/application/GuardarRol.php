<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Application;

use Ventas\Core\Roles\Domain\Repositorios\RolRepository;

final class GuardarRol
{
    public function __construct(private readonly RolRepository $rolRepository)
    {
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function ejecutar(array $datos): array
    {
        $rol = $this->rolRepository->guardar($datos);
        $respuesta = ['ok' => true, 'rol' => $rol->comoArray()];

        return $respuesta;
    }
}
