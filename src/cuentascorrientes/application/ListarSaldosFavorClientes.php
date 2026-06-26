<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ListarSaldosFavorClientes
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(): array
    {
        $saldos = $this->cuentaCorrienteRepository->saldosFavorClientes();

        return $saldos;
    }
}
