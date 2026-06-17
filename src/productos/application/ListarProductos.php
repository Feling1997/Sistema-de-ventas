<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class ListarProductos
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->productoRepository->listar();
    }
}
