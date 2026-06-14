<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Productos\CasosUso;

use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

final class EliminarProductosNoVendidos
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

    public function ejecutar(): int
    {
        return $this->productoRepository->eliminarNoVendidos();
    }
}
