<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Entidades\Producto;
use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

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
