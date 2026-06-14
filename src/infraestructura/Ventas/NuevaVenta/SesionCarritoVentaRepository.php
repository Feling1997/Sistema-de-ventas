<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;

final class SesionCarritoVentaRepository implements CarritoVentaRepository
{
    public function obtener(): array
    {
        $this->iniciarSesion();
        $carrito = [];

        if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
            $carrito = $_SESSION['carrito'];
        }

        return $carrito;
    }

    public function guardar(array $carrito): void
    {
        $this->iniciarSesion();
        $_SESSION['carrito'] = $carrito;
    }

    private function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
