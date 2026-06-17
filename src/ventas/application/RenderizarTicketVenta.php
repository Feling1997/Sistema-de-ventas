<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\ComprobanteVentaRepository;
use Ventas\Ventas\Domain\Repositorios\VentaRepository;

final class RenderizarTicketVenta
{
    public function __construct(
        private readonly VentaRepository $ventaRepository,
        private readonly ComprobanteVentaRepository $comprobanteVentaRepository
    ) {
    }

    public function ejecutar(int $idVenta, bool $autoPrint): array
    {
        $resultado = ['ok' => false, 'html' => '', 'error' => 'Venta invalida.'];
        $datos = $this->ventaRepository->obtenerComprobante($idVenta);

        if ($datos !== null) {
            $venta = (array) ($datos['venta'] ?? []);
            $items = (array) ($datos['items'] ?? []);
            $this->comprobanteVentaRepository->generarPdf($venta, $items, (int) ($venta['tipo_comprobante'] ?? 98));
            $resultado = [
                'ok' => true,
                'html' => $this->comprobanteVentaRepository->renderizarTicket($venta, $items, false, $autoPrint),
                'error' => '',
            ];
        }

        return $resultado;
    }
}
