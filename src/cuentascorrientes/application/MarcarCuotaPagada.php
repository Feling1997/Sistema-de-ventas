<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class MarcarCuotaPagada
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idCuota): bool
    {
        $ok = $this->cuentaCorrienteRepository->marcarCuotaPagada($idCuota);

        return $ok;
    }
}
