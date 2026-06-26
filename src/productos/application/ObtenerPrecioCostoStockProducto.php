<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class ObtenerPrecioCostoStockProducto
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(int $idStock): ?float
    {
        return $this->productoRepository->obtenerPrecioCostoStock($idStock);
    }
}
