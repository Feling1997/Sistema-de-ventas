<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ClienteVentaRepository;

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
