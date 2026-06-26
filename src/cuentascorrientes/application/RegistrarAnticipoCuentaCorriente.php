<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class RegistrarAnticipoCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $idCliente, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array
    {
        $resultado = $this->cuentaCorrienteRepository->registrarAnticipo($idCliente, $importe, $observacion, $idUsuario, $formaPago);

        return $resultado;
    }
}
