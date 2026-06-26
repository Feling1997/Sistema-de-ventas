<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Domain\Repositorios;

interface CrearCuentaCorrienteVentaRepository
{
    public function crearCuentaDesdeVenta(
        int $idVenta,
        int $idCliente,
        string $concepto,
        float $saldo,
        int $cantidadCuotas,
        string $primerVencimiento,
        array $vencimientos
    ): void;
}
