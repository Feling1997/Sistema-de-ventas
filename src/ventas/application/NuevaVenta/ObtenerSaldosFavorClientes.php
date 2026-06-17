<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\SaldoFavorClienteRepository;

final class ObtenerSaldosFavorClientes
{
    public function __construct(private readonly SaldoFavorClienteRepository $saldoFavorClienteRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->saldoFavorClienteRepository->obtenerSaldos();
    }
}
