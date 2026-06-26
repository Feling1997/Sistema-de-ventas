<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class EliminarProductoNoVendido
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(): int
    {
        return $this->productoRepository->eliminarNoVendido();
    }
}
