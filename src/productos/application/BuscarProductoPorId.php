<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Entidades\Producto;
use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class BuscarProductoPorId
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(int $id): ?Producto
    {
        return $this->productoRepository->buscarPorId($id);
    }
}
