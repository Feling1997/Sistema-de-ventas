<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class BuscarProductoPorCodigoBarras
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(string $codigo): ?array
    {
        return $this->productoRepository->buscarPorCodigoBarras($codigo);
    }
}
