<?php

declare(strict_types=1);

namespace Ventas\Precios\Domain\Repositorios;

interface PrecioRepository
{
    public function obtenerPrecioCostoStock(int $idStock): ?float;

    public function actualizarPreciosProductosPorStock(int $idStock, float $precioCosto): bool;

    public function recalcularListasPorStock(int $idStock, float $precioCosto): void;

    public function obtenerStocksEnDolares(): array;

    public function actualizarCostosPorCotizacion(float $cotizacion): void;
}
