<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

final class ObtenerPreciosProducto
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(int $idProducto): array
    {
        return $this->productoRepository->preciosProducto($idProducto);
    }
}
