<?php

declare(strict_types=1);

namespace Ventas\Dominio\Presupuestos\Repositorios;

interface ComprobantePresupuestoRepository
{
    public function renderizarTicket(array $presupuesto, array $items, bool $paraPdf, bool $autoPrint): string;

    public function generarPdf(array $presupuesto, array $items): bool;

    public function obtenerArchivoPdf(int $idPresupuesto): array;
}
