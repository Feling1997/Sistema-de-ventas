<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class MarcarAlertasLeidas
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idUsuario): void
    {
        $this->cuentaCorrienteRepository->marcarAlertasLeidas($idUsuario);
    }
}
