<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\Repositorios;

use Ventas\Ventas\Domain\Entidades\DetalleVenta;
use Ventas\Ventas\Domain\Entidades\Venta;

interface VentaRepository
{
    /**
     * @return Venta[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Venta;

    /**
     * @return DetalleVenta[]
     */
    public function obtenerDetalle(int $idVenta): array;

    public function obtenerComprobante(int $idVenta): ?array;

    public function listarPeriodo(string $fechaDesde, string $fechaHasta, string $ordenCampo, string $ordenDireccion): array;

    public function obtenerGananciasPorIds(array $ids): array;

    public function obtenerEstadosFiscales(array $ids): array;

    public function obtenerDetallesVentas(array $ids): array;

    public function confirmarVenta(int $idCliente, int $idUsuario, array $carrito, bool $controlarStock): array;

    public function buscarClienteFactura(int $idCliente): ?array;

    public function saldoFavorCliente(int $idCliente): float;

    public function crearFiscalPendiente(int $idVenta, string $tipoOperacion, int $tipoComprobante, array $configFiscal): bool;

    public function crearCuentaCorriente(
        int $idCliente,
        string $concepto,
        float $total,
        int $cuotas,
        string $primerVencimiento,
        ?int $idVenta,
        array $vencimientos
    ): bool;

    public function aplicarSaldoFavor(int $idCliente, int $idVenta, float $monto): bool;
}
