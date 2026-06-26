<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class RegistrarPagoCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idCuenta, array $cuotas, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array
    {
        $resultado = $this->cuentaCorrienteRepository->registrarPago($idCuenta, $cuotas, $importe, $observacion, $idUsuario, $formaPago);

        return $resultado;
    }
}
