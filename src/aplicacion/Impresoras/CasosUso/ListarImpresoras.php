<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Impresoras\CasosUso;

use Ventas\Dominio\Impresoras\Repositorios\ImpresoraRepository;

final class ListarImpresoras
{
    public function __construct(private readonly ImpresoraRepository $impresoraRepository)
    {
    }

    /**
     * @return array<int, string>
     */
    public function ejecutar(): array
    {
        return $this->impresoraRepository->listar();
    }
}
