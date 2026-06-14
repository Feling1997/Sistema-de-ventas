<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\SaldoFavorClienteRepository;

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
