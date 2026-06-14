<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\Entidades\Venta;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

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
