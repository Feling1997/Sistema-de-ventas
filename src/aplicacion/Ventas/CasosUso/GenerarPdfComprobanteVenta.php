<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\Repositorios\ComprobanteVentaRepository;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class GenerarPdfComprobanteVenta
{
    public function __construct(
        private readonly VentaRepository $ventaRepository,
        private readonly ComprobanteVentaRepository $comprobanteVentaRepository
    ) {
    }

    public function ejecutar(int $idVenta): array
    {
        $resultado = ['ok' => false, 'error' => 'Venta invalida.'];
        $datos = $this->ventaRepository->obtenerComprobante($idVenta);

        if ($datos !== null) {
            $venta = (array) ($datos['venta'] ?? []);
            $items = (array) ($datos['items'] ?? []);
            $ok = $this->comprobanteVentaRepository->generarPdf($venta, $items, (int) ($venta['tipo_comprobante'] ?? 98));
            $resultado = [
                'ok' => $ok,
                'error' => $ok ? '' : 'No se pudo generar el PDF.',
            ];
        }

        return $resultado;
    }
}
