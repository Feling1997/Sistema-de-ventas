<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;

final class CrearSesionSinLogin
{
    public function __construct(private readonly SesionAuthRepository $sesionAuthRepository)
    {
    }

    public function ejecutar(): array
    {
        $usuario = [
            'id' => 0,
            'usuario' => 'Sin login',
            'rol' => 'ADMIN',
            'permisos' => [],
        ];
        $this->sesionAuthRepository->guardarUsuario($usuario);

        return $usuario;
    }
}
