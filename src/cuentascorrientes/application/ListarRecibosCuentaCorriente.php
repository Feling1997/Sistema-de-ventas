<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Application;

use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class ListarRecibosCuentaCorriente
{
    public function __construct(private readonly CuentaCorrienteRepository $cuentaCorrienteRepository)
    {
    }

    public function ejecutar(int $limite = 50, string $ordenSql = 'r.fecha DESC'): array
    {
        $recibos = $this->cuentaCorrienteRepository->listarRecibos($limite, $ordenSql);

        return $recibos;
    }
}
