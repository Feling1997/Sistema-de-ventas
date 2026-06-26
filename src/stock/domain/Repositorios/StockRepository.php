<?php

declare(strict_types=1);

namespace Ventas\Stock\Domain\Repositorios;

use Ventas\Stock\Domain\Entidades\Stock;

interface StockRepository
{
    /**
     * @return Stock[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Stock;

    /**
     * @return Stock[]
     */
    public function listarGeneralesActivos(): array;

    public function crear(
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = 'general',
        string $monedaCosto = 'ARS',
        float $costoOrigen = 0
    ): bool;

    public function crearRetornandoId(
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = 'general',
        string $monedaCosto = 'ARS',
        float $costoOrigen = 0
    ): int;

    public function actualizar(
        int $id,
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = '',
        string $monedaCosto = '',
        float $costoOrigen = 0
    ): bool;

    public function sumarCantidad(int $id, float $cantidad): bool;

    public function contarProductosAsociados(int $idStock): int;

    public function estaAsociadoAProductos(int $idStock): bool;

    public function eliminar(int $id): bool;

    public function recalcularPreciosProductosPorStock(int $idStock): bool;

    public function recalcularCostosPorCotizacion(): int;

    public function alertasStockBajo(int $idUsuario = 0, bool $mostrarLeidas = true, string $filtro = 'bajo'): array;

    public function resumenAlertasStockBajo(int $idUsuario = 0): array;

    public function marcarAlertaLeida(int $idProducto, int $idUsuario): bool;

    public function listarFaltantes(bool $soloMinimo = true): array;

    public function obtenerCotizacionDolar(): float;

    public function obtenerCostoStock(int $id): float;

    public function obtenerStockActivo(): array;

    public function obtenerStockPorProducto(int $idProducto): ?Stock;

    public function inicializarEsquemaStock(): void;

    public function inicializarAlertasStock(): void;
}
