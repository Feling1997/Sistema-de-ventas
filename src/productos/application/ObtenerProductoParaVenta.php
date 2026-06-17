<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class ObtenerProductoParaVenta
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(int $idProducto): ?array
    {
        return $this->productoRepository->obtenerProductoParaVenta($idProducto);
    }
}
