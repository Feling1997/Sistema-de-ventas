<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ListarCuotasPendientesDetalle
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(string $buscar = '', string $estado = 'todos', string $orden = 'vencimiento', string $direccion = 'ASC'): array
    {
        $cuotas = $this->cuentaCorrienteRepository->cuotasPendientesDetalle($buscar, $estado, $orden, $direccion);

        return $cuotas;
    }
}
