<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ObtenerResumenGeneralCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(): array
    {
        $resumen = $this->cuentaCorrienteRepository->resumenGeneral();

        return $resumen;
    }
}
