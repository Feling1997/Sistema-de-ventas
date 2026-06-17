<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Entidades\Venta;
use Ventas\Ventas\Domain\Repositorios\VentaRepository;

final class BuscarVentaPorId
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(int $id): ?Venta
    {
        return $this->ventaRepository->buscarPorId($id);
    }
}
