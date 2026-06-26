<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class BuscarReciboCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $id): ?array
    {
        $recibo = $this->cuentaCorrienteRepository->buscarRecibo($id);

        return $recibo;
    }
}
