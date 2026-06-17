<?php

declare(strict_types=1);

namespace Ventas\Auth\Application;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;

final class AutenticarUsuario
{
    public function __construct(
        private readonly UsuarioRepository $usuarioRepository,
        private readonly SesionAuthRepository $sesionAuthRepository
    ) {
    }

    public function ejecutar(string $usuario, string $clave): array
    {
        $resultado = [
            'ok' => false,
            'error' => '',
            'usuario' => null,
        ];
        $usuarioNormalizado = trim($usuario);
        $claveNormalizada = trim($clave);

        if ($usuarioNormalizado === '' || $claveNormalizada === '') {
            $resultado['error'] = 'Completa usuario o contrasena';
        } else {
            $usuarioEncontrado = $this->usuarioRepository->buscarPorUsuario($usuarioNormalizado);

            if ($usuarioEncontrado === null) {
                $resultado['error'] = 'Usuario o contrasena incorrectos';
            } elseif (!$usuarioEncontrado->activo()) {
                $resultado['error'] = 'Usuario inactivo';
            } elseif (!password_verify($claveNormalizada, (string) $usuarioEncontrado->claveHash())) {
                $resultado['error'] = 'Usuario o contrasena incorrectos';
            } else {
                $usuarioSesion = [
                    'id' => (int) $usuarioEncontrado->id(),
                    'usuario' => $usuarioEncontrado->usuario(),
                    'rol' => $usuarioEncontrado->rol(),
                    'permisos' => $usuarioEncontrado->permisos()->comoArray(),
                ];
                $this->sesionAuthRepository->guardarUsuario($usuarioSesion);
                $resultado = [
                    'ok' => true,
                    'error' => '',
                    'usuario' => $usuarioSesion,
                ];
            }
        }

        return $resultado;
    }
}
