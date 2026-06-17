<?php

declare(strict_types=1);

namespace Ventas\Ventas\Infrastructure\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\FormularioVentaRepository;

final class SesionFormularioVentaRepository implements FormularioVentaRepository
{
    public function obtener(): array
    {
        $this->iniciarSesion();
        $datos = [];

        if (isset($_SESSION['flash_form_data']['ventas_form']) && is_array($_SESSION['flash_form_data']['ventas_form'])) {
            $datos = $_SESSION['flash_form_data']['ventas_form'];
        }

        unset($_SESSION['flash_form_data']['ventas_form']);

        if (isset($_SESSION['flash_form_data']) && count($_SESSION['flash_form_data']) === 0) {
            unset($_SESSION['flash_form_data']);
        }

        return $datos;
    }

    public function guardar(array $datos): void
    {
        $this->iniciarSesion();
        $_SESSION['flash_form_data']['ventas_form'] = [
            'id_cliente' => (int) ($datos['id_cliente'] ?? 1),
            'buscar_cliente' => (string) ($datos['buscar_cliente'] ?? ''),
            'id_producto' => (string) ($datos['id_producto'] ?? ''),
            'cantidad' => $datos['cantidad'] ?? 1,
            'descuento' => $datos['descuento'] ?? 0,
            'precio_unit' => (string) ($datos['precio_unit'] ?? ''),
            'tipo_comprobante' => (int) ($datos['tipo_comprobante'] ?? 98),
            'buscar_producto' => (string) ($datos['buscar_producto'] ?? ''),
            'id_lista_precio' => (int) ($datos['id_lista_precio'] ?? 1),
        ];
    }

    private function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
