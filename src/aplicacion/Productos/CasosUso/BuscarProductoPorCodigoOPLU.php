<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

final class BuscarProductoPorCodigoOPLU
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(string $codigo): ?array
    {
        $producto = $this->productoRepository->buscarPorCodigoOPluVenta($codigo);

        return $producto;
    }
}
