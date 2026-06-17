<?php

declare(strict_types=1);

namespace Ventas\Auth\Infrastructure;

use Ventas\Auth\Domain\Repositorios\SesionAuthRepository;

final class SesionPhpAuthRepository implements SesionAuthRepository
{
    public function obtenerUsuario(): ?array
    {
        $this->iniciarSesion();
        $usuario = null;

        if (isset($_SESSION['usuario_logueado']) && is_array($_SESSION['usuario_logueado'])) {
            $usuario = $_SESSION['usuario_logueado'];
        }

        return $usuario;
    }

    public function guardarUsuario(array $usuario): void
    {
        $this->iniciarSesion();
        $_SESSION['usuario_logueado'] = $usuario;
    }

    public function limpiar(): void
    {
        $this->iniciarSesion();
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
