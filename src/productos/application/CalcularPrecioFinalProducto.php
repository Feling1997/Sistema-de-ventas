<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class CalcularPrecioFinalProducto
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(float $precioCosto, float $factorConversion, float $ganancia): float
    {
        return $this->productoRepository->calcularPrecioFinal($precioCosto, $factorConversion, $ganancia);
    }
}
