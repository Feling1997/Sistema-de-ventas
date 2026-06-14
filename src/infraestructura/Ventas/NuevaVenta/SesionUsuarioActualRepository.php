<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\UsuarioActualRepository;

final class SesionUsuarioActualRepository implements UsuarioActualRepository
{
    public function obtener(): array
    {
        $this->iniciarSesion();
        $usuario = [];

        if (isset($_SESSION['usuario_logueado']) && is_array($_SESSION['usuario_logueado'])) {
            $usuario = $_SESSION['usuario_logueado'];
        }

        return $usuario;
    }

    private function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
