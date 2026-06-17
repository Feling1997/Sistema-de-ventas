<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;

final class ObtenerSesionActual
{
    public function __construct(private readonly SesionAuthRepository $sesionAuthRepository)
    {
    }

    public function ejecutar(): ?array
    {
        $usuario = $this->sesionAuthRepository->obtenerUsuario();

        return $usuario;
    }
}
