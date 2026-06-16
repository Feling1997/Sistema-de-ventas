<?php

declare(strict_types=1);

namespace Ventas\Impresoras\Application;

use Ventas\Impresoras\Domain\Repositorios\ImpresoraRepository;

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
