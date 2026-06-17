<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;

final class IniciarSesionAuth
{
    public function __construct(private readonly SesionAuthRepository $sesionAuthRepository)
    {
    }

    public function ejecutar(array $usuario): array
    {
        $usuarioSesion = [
            'id' => (int) ($usuario['id'] ?? 0),
            'usuario' => (string) ($usuario['usuario'] ?? ''),
            'rol' => (string) ($usuario['rol'] ?? ''),
            'permisos' => is_array($usuario['permisos'] ?? null) ? array_values($usuario['permisos']) : [],
        ];
        $this->sesionAuthRepository->guardarUsuario($usuarioSesion);

        return $usuarioSesion;
    }
}
