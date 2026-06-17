<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\Repositorios;

interface ComprobanteVentaRepository
{
    public function renderizarTicket(array $venta, array $items, bool $paraPdf, bool $autoPrint): string;

    public function generarPdf(array $venta, array $items, int $tipoComprobante): bool;

    public function obtenerArchivoPdf(int $idVenta): array;
}
