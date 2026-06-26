<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ListarCuotasPendientes
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idCuenta): array
    {
        $cuotas = $this->cuentaCorrienteRepository->cuotasPendientes($idCuenta);

        return $cuotas;
    }
}
