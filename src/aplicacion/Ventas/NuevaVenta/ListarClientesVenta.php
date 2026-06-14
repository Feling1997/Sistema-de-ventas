<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ClienteVentaRepository;

final class ListarClientesVenta
{
    public function __construct(private readonly ClienteVentaRepository $clienteVentaRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->clienteVentaRepository->listarParaVenta();
    }
}
