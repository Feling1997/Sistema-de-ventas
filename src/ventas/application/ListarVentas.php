<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\VentaRepository;

final class ListarVentas
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->ventaRepository->listar();
    }
}
