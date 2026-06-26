<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ObtenerCantidadVencidasNoLeidas
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idUsuario): int
    {
        $cantidad = $this->cuentaCorrienteRepository->cantidadVencidasNoLeidas($idUsuario);

        return $cantidad;
    }
}
