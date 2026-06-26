<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class CancelarCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idCuenta): bool
    {
        $ok = $this->cuentaCorrienteRepository->cancelarCuenta($idCuenta);

        return $ok;
    }
}
