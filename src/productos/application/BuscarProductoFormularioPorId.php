<?php

declare(strict_types=1);

namespace Ventas\Productos\Application;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;

final class BuscarProductoFormularioPorId
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(int $id): ?array
    {
        return $this->productoRepository->buscarFormularioPorId($id);
    }
}
